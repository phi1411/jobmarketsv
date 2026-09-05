<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\CompanyService;
use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\CompanyRepository;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Exceptions\AuthorizationException;
use JobMarket\Exceptions\NotFoundException;
use JobMarket\Exceptions\ValidationException;

class CompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $repo = new CompanyRepository();
        $user = $request->getUser();
        $isAdmin = $user !== null && ($user["role"] ?? "") === "admin";

        if ($isAdmin) {
            $companies = $repo->getAll();
            return Response::success($companies, "Danh sách toàn bộ công ty (Quản trị viên).");
        }

        $companies = $repo->getAllVerifiedPublic();
        return Response::success($companies, "Danh sách công ty.");
    }

    public function store(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để tạo hồ sơ công ty.");
        }
        if (($user["role"] ?? "") !== "company") {
            throw new AuthorizationException("Chỉ tài khoản Nhà tuyển dụng mới có quyền tạo hồ sơ công ty.");
        }

        $repo = new CompanyRepository();
        $existing = $repo->findByUserId($user["id"]);
        if ($existing) {
            throw new ValidationException(["company" => ["Tài khoản này đã có hồ sơ công ty."]]);
        }

        $data = $request->all();
        if (empty($data["name"]) || strlen(trim($data["name"])) < 2) {
            throw new ValidationException(["name" => ["Tên công ty là bắt buộc và phải có tối thiểu 2 ký tự."]]);
        }

        $params = [
            "user_id"     => $user["id"],
            "name"        => trim((string)$data["name"]),
            "description" => isset($data["description"]) ? trim((string)$data["description"]) : "",
            "location"    => isset($data["location"]) ? trim((string)$data["location"]) : (isset($data["address"]) ? trim((string)$data["address"]) : ""),
            "website"     => isset($data["website"]) ? trim((string)$data["website"]) : ""
        ];

        (new CompanyService($repo))->create($params);
        $created = $repo->findByUserId($user["id"]);
        return Response::created($created, "Tạo hồ sơ công ty thành công.");
    }

    public function show(Request $request, string $id): Response
    {
        $repo = new CompanyRepository();
        $company = $repo->getById($id);
        if (empty($company)) {
            throw new NotFoundException("Không tìm thấy thông tin công ty.");
        }

        $user = $request->getUser();
        $isOwner = $user !== null && ($company["user_id"] ?? "") === ($user["id"] ?? "");
        $isAdmin = $user !== null && ($user["role"] ?? "") === "admin";

        if (!$isOwner && !$isAdmin) {
            // Unverified companies are hidden from non-owner/non-admin
            if (($company["verification_status"] ?? "") !== "verified") {
                throw new NotFoundException("Không tìm thấy thông tin công ty.");
            }
            return Response::success(CompanyRepository::sanitizePublic($company), "Thông tin công ty công khai.");
        }

        return Response::success($company, "Thông tin chi tiết công ty.");
    }

    public function update(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để cập nhật thông tin công ty.");
        }
        if (($user["role"] ?? "") !== "company") {
            throw new AuthorizationException("Chỉ tài khoản Nhà tuyển dụng mới có quyền cập nhật thông tin công ty.");
        }

        $repo = new CompanyRepository();
        $company = $repo->getById($id);
        if (empty($company)) {
            throw new NotFoundException("Không tìm thấy thông tin công ty.");
        }

        if (($company["user_id"] ?? "") !== $user["id"]) {
            throw new AuthorizationException("Bạn không có quyền chỉnh sửa công ty không thuộc sở hữu của mình.");
        }

        $data = $request->all();
        $name = isset($data["name"]) ? trim((string)$data["name"]) : ($company["name"] ?? "");
        if (empty($name) || strlen($name) < 2) {
            throw new ValidationException(["name" => ["Tên công ty phải có tối thiểu 2 ký tự."]]);
        }

        // Anti-Mass Assignment: NEVER let user update user_id, verification_status, rejection_reason, or id
        $repo->updateProfile($id, [
            "name"           => $name,
            "description"    => isset($data["description"]) ? trim((string)$data["description"]) : ($company["description"] ?? null),
            "contact_person" => isset($data["contact_person"]) ? trim((string)$data["contact_person"]) : ($company["contact_person"] ?? null),
            "contact_phone"  => isset($data["contact_phone"]) ? trim((string)$data["contact_phone"]) : ($company["contact_phone"] ?? null),
            "address"        => isset($data["address"]) ? trim((string)$data["address"]) : ($company["address"] ?? null),
            "city"           => isset($data["city"]) ? trim((string)$data["city"]) : ($company["city"] ?? null),
            "district"       => isset($data["district"]) ? trim((string)$data["district"]) : ($company["district"] ?? null),
            "website"        => isset($data["website"]) ? trim((string)$data["website"]) : ($company["website"] ?? null),
        ]);

        $fresh = $repo->getById($id);
        return Response::success($fresh, "Cập nhật công ty thành công.");
    }

    public function destroy(Request $request, string $id): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để thực hiện thao tác này.");
        }
        if (($user["role"] ?? "") !== "company") {
            throw new AuthorizationException("Chỉ tài khoản Nhà tuyển dụng mới có quyền thao tác trên công ty.");
        }

        $repo = new CompanyRepository();
        $company = $repo->getById($id);
        if (empty($company)) {
            throw new NotFoundException("Không tìm thấy thông tin công ty.");
        }

        if (($company["user_id"] ?? "") !== $user["id"]) {
            throw new AuthorizationException("Bạn không có quyền xóa công ty không thuộc sở hữu của mình.");
        }

        // Refuse hard-delete via API to preserve jobs and applications integrity
        throw new AuthorizationException("Hệ thống không hỗ trợ xóa công ty qua API để bảo đảm toàn vẹn dữ liệu tuyển dụng.");
    }

    public function myProfile(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem thông tin công ty.");
        }
        if (($user["role"] ?? "") !== "company") {
            throw new AuthorizationException("Chỉ tài khoản Nhà tuyển dụng mới có quyền truy cập hồ sơ công ty.");
        }

        $company = (new CompanyRepository())->findByUserId($user["id"]);
        if (!$company) {
            throw new NotFoundException("Không tìm thấy thông tin công ty.");
        }

        return Response::success($company, "Thông tin hồ sơ công ty.");
    }

    public function updateMyProfile(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để cập nhật thông tin công ty.");
        }
        if (($user["role"] ?? "") !== "company") {
            throw new AuthorizationException("Chỉ tài khoản Nhà tuyển dụng mới có quyền cập nhật hồ sơ công ty.");
        }

        $repo = new CompanyRepository();
        $company = $repo->findByUserId($user["id"]);
        if (!$company) {
            throw new NotFoundException("Không tìm thấy thông tin công ty.");
        }

        $data = $request->all();

        // Validation
        if (empty($data["name"]) || strlen(trim($data["name"])) < 2) {
            throw new ValidationException(["name" => ["Tên công ty là bắt buộc và phải có tối thiểu 2 ký tự."]]);
        }

        // Anti-Mass Assignment: NEVER let user update verification_status, rejection_reason, user_id, or id
        $repo->updateProfile($company["id"], [
            "name"           => trim((string)$data["name"]),
            "description"    => isset($data["description"]) ? trim((string)$data["description"]) : null,
            "contact_person" => isset($data["contact_person"]) ? trim((string)$data["contact_person"]) : null,
            "contact_phone"  => isset($data["contact_phone"]) ? trim((string)$data["contact_phone"]) : null,
            "address"        => isset($data["address"]) ? trim((string)$data["address"]) : null,
            "city"           => isset($data["city"]) ? trim((string)$data["city"]) : null,
            "district"       => isset($data["district"]) ? trim((string)$data["district"]) : null,
            "website"        => isset($data["website"]) ? trim((string)$data["website"]) : null,
        ]);

        $fresh = $repo->findByUserId($user["id"]);
        return Response::success($fresh, "Cập nhật hồ sơ công ty thành công.");
    }
}

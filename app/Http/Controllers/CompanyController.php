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
    public function index(Request $request)
    {
        return (new CompanyService(new CompanyRepository()))->getAll();
    }

    public function store(Request $request)
    {
        (new CompanyService(new CompanyRepository()))->create($request->postParams);
    }

    public function show(Request $request, string $id)
    {
        $company = (new CompanyService(new CompanyRepository()))->getById($id);
        if (empty($company)) {
            throw new NotFoundException("Không tìm thấy thông tin công ty.");
        }
        return $company;
    }

    public function update(Request $request, string $id)
    {
        $params = $request->all();
        $params["id"] = $id;
        (new CompanyService(new CompanyRepository()))->update($params);
        return ["message" => "Cập nhật công ty thành công."];
    }

    public function destroy(Request $request, string $id)
    {
        (new CompanyService(new CompanyRepository()))->delete($id);
        return ["message" => "Xóa công ty thành công."];
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

<?php

namespace JobMarket\Http\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use JobMarket\Domain\CvBuilder\CvHtmlRenderer;
use JobMarket\Domain\CvBuilder\OnlineCvService;
use JobMarket\Domain\Cv\StudentCvService;
use JobMarket\Exceptions\AppException;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Middlewares\RoleMiddleware;
use JobMarket\Http\Request;
use JobMarket\Http\Response;

class OnlineCvController extends Controller
{
    private OnlineCvService $service;
    private CvHtmlRenderer $renderer;

    public function __construct(?OnlineCvService $service = null, ?CvHtmlRenderer $renderer = null)
    {
        $this->service = $service ?? new OnlineCvService();
        $this->renderer = $renderer ?? new CvHtmlRenderer();
    }

    public function templates(Request $request): Response
    {
        return Response::success($this->service->templates(), 'Danh sách mẫu CV sinh viên.');
    }

    public function index(Request $request): Response
    {
        return Response::success($this->service->list($this->student($request)), 'Danh sách CV của bạn.');
    }

    public function sources(Request $request): Response
    {
        return Response::success(
            $this->service->sourceOptions($this->student($request)),
            'Các nguồn dữ liệu có thể dùng để tạo CV.'
        );
    }

    public function store(Request $request): Response
    {
        $cv = $this->service->create($this->student($request), $request->all());
        return Response::created($cv, 'Tạo CV thành công.');
    }

    public function show(Request $request, string $id): Response
    {
        return Response::success($this->service->get($this->student($request), $id), 'Chi tiết CV.');
    }

    public function update(Request $request, string $id): Response
    {
        $cv = $this->service->update($this->student($request), $id, $request->all());
        return Response::success($cv, 'Đã lưu CV.');
    }

    public function duplicate(Request $request, string $id): Response
    {
        $cv = $this->service->duplicate($this->student($request), $id);
        return Response::created($cv, 'Nhân bản CV thành công.');
    }

    public function setPrimary(Request $request, string $id): Response
    {
        $payload = ['is_primary' => true];
        if ($request->input('expected_version') !== null) {
            $payload['expected_version'] = $request->input('expected_version');
        }
        $cv = $this->service->update($this->student($request), $id, $payload);
        return Response::success($cv, 'Đã đặt làm CV chính.');
    }

    public function visibility(Request $request, string $id): Response
    {
        $payload = ['is_public' => $request->input('is_public')];
        if ($request->input('expected_version') !== null) {
            $payload['expected_version'] = $request->input('expected_version');
        }
        $cv = $this->service->update($this->student($request), $id, $payload);
        return Response::success($cv, !empty($cv['is_public']) ? 'Đã bật chia sẻ CV.' : 'Đã tắt chia sẻ CV.');
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->service->delete($this->student($request), $id);
        return Response::success(null, 'Đã xóa CV.');
    }

    public function preview(Request $request, string $id): Response
    {
        $cv = $this->service->ownedRaw($this->student($request), $id);
        return Response::html($this->renderer->render($cv));
    }

    public function export(Request $request, string $id): Response
    {
        $cv = $this->service->ownedRaw($this->student($request), $id);
        $response = $this->pdfResponse($cv);
        $this->service->markExported($id);
        return $response;
    }

    public function activate(Request $request, string $id): Response
    {
        $user = $this->student($request);
        $payload = ['is_primary' => true];
        if ($request->input('expected_version') !== null) {
            $payload['expected_version'] = $request->input('expected_version');
        }

        $cv = $this->service->update($user, $id, $payload);
        $raw = $this->service->ownedRaw($user, $id);
        $pdf = $this->renderPdfBytes($raw);
        $tempPath = tempnam(sys_get_temp_dir(), 'online-cv-');
        if ($tempPath === false || file_put_contents($tempPath, $pdf) === false) {
            if (is_string($tempPath)) {
                @unlink($tempPath);
            }
            throw new AppException('Không thể chuẩn bị CV để dùng khi ứng tuyển.', 500);
        }

        $safeName = $this->safePdfName((string)($raw['title'] ?? 'CV'));
        try {
            $activeCv = (new StudentCvService())->uploadActiveCv($user, [
                'name' => $safeName,
                'type' => 'application/pdf',
                'tmp_name' => $tempPath,
                'error' => UPLOAD_ERR_OK,
                'size' => strlen($pdf),
            ]);
        } finally {
            @unlink($tempPath);
        }

        return Response::success([
            'cv' => $cv,
            'active_cv' => $activeCv,
        ], 'Đã đặt CV này làm hồ sơ dùng khi ứng tuyển.');
    }

    public function publicShow(Request $request, string $slug): Response
    {
        return Response::success($this->service->publicBySlug($slug), 'CV công khai.');
    }

    public function publicExport(Request $request, string $slug): Response
    {
        $cv = $this->service->publicRaw($slug);
        $response = $this->pdfResponse($cv);
        $this->service->markExported((string)$cv['id']);
        return $response;
    }

    private function student(Request $request): array
    {
        $user = $request->getUser();
        if ($user === null) {
            throw new AuthenticationException('Vui lòng đăng nhập để sử dụng trình tạo CV.');
        }
        RoleMiddleware::check($request, ['student', 'developer']);
        return $user;
    }

    private function pdfResponse(array $cv): Response
    {
        return Response::binary($this->renderPdfBytes($cv), 'application/pdf', [
            'Content-Disposition' => 'attachment; filename="' . $this->safePdfName((string)($cv['title'] ?? 'CV')) . '"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function renderPdfBytes(array $cv): string
    {
        if (!class_exists(Dompdf::class)) {
            throw new AppException('Máy chủ chưa cài bộ xuất PDF.', 503);
        }

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->renderer->render($cv, true), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    private function safePdfName(string $name): string
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', trim($name)) ?: 'CV';
        $safeName = trim($safeName, '-');
        return ($safeName !== '' ? $safeName : 'CV') . '.pdf';
    }
}

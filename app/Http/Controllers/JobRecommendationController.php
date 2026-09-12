<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\JobRecommendationService;
use JobMarket\Exceptions\AuthenticationException;
use JobMarket\Http\Request;
use JobMarket\Http\Response;

class JobRecommendationController extends Controller
{
    private JobRecommendationService $recommendations;

    public function __construct(?JobRecommendationService $recommendations = null)
    {
        $this->recommendations = $recommendations ?? new JobRecommendationService();
    }

    public function index(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user) {
            throw new AuthenticationException("Vui lòng đăng nhập để xem việc làm dành cho bạn.");
        }

        $limit = isset($request->getParams["limit"]) ? (int)$request->getParams["limit"] : 12;
        $minimumScore = isset($request->getParams["minimum_score"]) ? (int)$request->getParams["minimum_score"] : 0;

        return Response::success(
            $this->recommendations->recommend($user, $limit, $minimumScore),
            "Danh sách việc làm được cá nhân hóa từ hồ sơ và lịch rảnh của bạn."
        );
    }
}

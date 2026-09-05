<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\SkillRepository;

class SkillController extends Controller
{
    private SkillRepository $skillRepo;

    public function __construct()
    {
        $this->skillRepo = new SkillRepository();
    }

    public function index(Request $request): Response
    {
        $skills = $this->skillRepo->getAll();
        return Response::success($skills, "Danh sách kỹ năng.");
    }

    public function show(Request $request, string $id)
    {
    }

    public function store(Request $request)
    {
    }

    public function update(Request $request, string $id)
    {
    }

    public function destroy(Request $request, string $id)
    {
    }
}
<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Http\Request;
use JobMarket\Http\Response;
use JobMarket\Infrastructure\CategoryRepository;

class CategoryController extends Controller
{
    private CategoryRepository $categoryRepo;

    public function __construct()
    {
        $this->categoryRepo = new CategoryRepository();
    }

    public function index(Request $request): Response
    {
        $categories = $this->categoryRepo->getAll();
        return Response::success($categories, "Danh sách danh mục việc làm.");
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

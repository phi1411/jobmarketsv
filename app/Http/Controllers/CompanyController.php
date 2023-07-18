<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\CompanyService;
use JobMarket\Http\Request;
use JobMarket\Infrastructure\CompanyRepository;

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

    public function show(Request $request, int $id)
    {
        return (new CompanyService(new CompanyRepository()))->getById($id);
    }

    public function update(Request $request, int $id)
    {
        (new CompanyService(new CompanyRepository()))->update($request->postParams);
    }

    public function destroy(Request $request, int $id)
    {
        (new CompanyService(new CompanyRepository()))->delete($id);
    }
}

<?php

namespace JobMarket\Http\Controllers;

use JobMarket\Domain\JobService;
use JobMarket\Http\Request;
use JobMarket\Infrastructure\JobRepository;

class JobController extends Controller
{
    public function index(Request $request)
    {
        return (new JobService(new JobRepository()))->getAll();
    }

    public function store(Request $request)
    {

    }

    public function show(Request $request, int $id)
    {

    }

    public function update(Request $request, int $id)
    {

    }

    public function destroy(Request $request, int $id)
    {
        
    }
}
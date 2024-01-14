@extends('panel.layout.app')
@section('title', 'Marketplace')

@section('content')
    <div class="page-header">
        <div class="container-xl">
            <div class="row g-2 items-center justify-between max-md:flex-col max-md:items-start max-md:gap-4">
                <div class="col">
                    <a href="/dashboard" class="page-pretitle flex items-center">
                        <svg class="!me-2 rtl:-scale-x-100" width="8" height="10" viewBox="0 0 6 10" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4.45536 9.45539C4.52679 9.45539 4.60714 9.41968 4.66071 9.36611L5.10714 8.91968C5.16071 8.86611 5.19643 8.78575 5.19643 8.71432C5.19643 8.64289 5.16071 8.56254 5.10714 8.50896L1.59821 5.00004L5.10714 1.49111C5.16071 1.43753 5.19643 1.35718 5.19643 1.28575C5.19643 1.20539 5.16071 1.13396 5.10714 1.08039L4.66071 0.633963C4.60714 0.580392 4.52679 0.544678 4.45536 0.544678C4.38393 0.544678 4.30357 0.580392 4.25 0.633963L0.0892856 4.79468C0.0357141 4.84825 0 4.92861 0 5.00004C0 5.07146 0.0357141 5.15182 0.0892856 5.20539L4.25 9.36611C4.30357 9.41968 4.38393 9.45539 4.45536 9.45539Z"/>
                        </svg>
                        {{__('Back to dashboard')}}
                    </a>
                    <h2 class="page-title mb-2">
                        {{__('Marketplace')}}
                    </h2>
                </div>
                
            </div>
            <div class="flex flex-row justify-start mt-4">
                <div class="row g-2 flex justify-start w-full">
                    <div class="input-icon">
                        <span class="input-icon-addon ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="17" height="17" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"></path><path d="M21 21l-6 -6"></path></svg>
                        </span>
                        <input type="search" class="form-control navbar-search-input peer max-lg:!rounded-md dark:!bg-zinc-900" id="search_str" placeholder="Search" aria-label="Search in website">
                    </div>
                </div>
            </div>
            <div class="flex flex-row justify-start items-center mt-3 gap-3">
                <button class="addons_filter text-[#2B2F37] bg-[--lqd-header-search-bg] font-medium text-[13px] rounded-[55px] px-2 py-1 cursor-pointer bg-none border-none transition-all [&.active]:bg-[--tblr-primary] [&.active]:text-white active" data-filter="All">ALL</button>
                <button class="addons_filter text-[#2B2F37] bg-[--lqd-header-search-bg] font-medium text-[13px] rounded-[55px] px-2 py-1 cursor-pointer bg-none border-none transition-all [&.active]:bg-[--tblr-primary] [&.active]:text-white" data-filter="Favorite">Installed</button>
                <button class="addons_filter text-[#2B2F37] bg-[--lqd-header-search-bg] font-medium text-[13px] rounded-[55px] px-2 py-1 cursor-pointer bg-none border-none transition-all [&.active]:bg-[--tblr-primary] [&.active]:text-white" data-filter="Free">Free</button>
                <button class="addons_filter text-[#2B2F37] bg-[--lqd-header-search-bg] font-medium text-[13px] rounded-[55px] px-2 py-1 cursor-pointer bg-none border-none transition-all [&.active]:bg-[--tblr-primary] [&.active]:text-white" data-filter="Paid">Paid</button>
                {{-- @foreach ($categoryList as $category)
                    <button class="chat_filter text-[#2B2F37] bg-[--lqd-header-search-bg] font-medium text-[13px] rounded-[55px] px-2 py-1 cursor-pointer bg-none border-none transition-all [&.active]:bg-[--tblr-primary] [&.active]:text-white" data-filter="{{$category->name}}">{{$category->name}}</button>
                @endforeach --}}
            </div>
        </div>
    </div>
    <!-- Page body -->
    <div class="page-body pt-6">
        <div class="container-xl">
            
        </div>
    </div>

@endsection

@section('script')
    <script src="/assets/js/panel/marketplace.js"></script> 
@endsection

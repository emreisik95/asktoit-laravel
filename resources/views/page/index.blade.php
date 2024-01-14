@extends('layout.app')

@section('content')

<section class="site-section flex items-center justify-center min-h-[200px] text-center text-white relative pt-64 pb-52 max-md:pb-16 max-md:pt-48 overflow-hidden bg-[#4384ea]" id="banner">
	<div class="absolute w-full h-full top-0 start-0 overflow-hidden">
		<div class="banner-bg w-full h-full absolute top-0 left-0"></div>
	</div>
	<div class="container relative">
		<div class="max-lg:w-2/3 max-md:w-full flex flex-col items-center w-1/2 mx-auto">
			<div class="banner-title-wrap relative">
				<h1
					class="
					banner-title
				    font-golos -tracking-wide font-bold text-white
					opacity-0 transition-all ease-out translate-y-7
					group-[.page-loaded]/body:opacity-100 group-[.page-loaded]/body:translate-y-0">
					{{$page->title}}
				</h1>
			</div>
		</div>
	</div>
	<div class="banner-divider absolute -bottom-[2px] inset-x-0">
		<svg class="fill-body-bg w-full h-auto" width="1440" height="105" viewBox="0 0 1440 105" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
			<path d="M0 0C240 68.7147 480 103.072 720 103.072C960 103.072 1200 68.7147 1440 0V104.113H0V0Z"/>
		</svg>
	</div>
</section>

<section class="page-content">
    <div class="container py-20">
        {!! $page->content !!}
    </div>
</section>

@endsection

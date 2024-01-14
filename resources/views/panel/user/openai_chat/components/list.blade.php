<div
    class="page-body mt-2 relative after:h-px after:w-full after:bg-[var(--tblr-body-bg)] after:absolute after:top-full after:left-0 after:-mt-px">
    <div class="container-fluid">
        <div class="row">
            @foreach ($aiList as $entry)
                <div data-filter="medical" category="{{$entry->category}}" id="{{$entry->id}}"
                    class="chat_element col-lg-4 col-xl-3 col-md-6 py-8 10 px-16 relative border-b border-solid border-t-0 border-s-0 border-[var(--tblr-border-color)] group max-xl:px-10 pt-[48px]">
                    <div class="absolute top-[10px] left-[20px] fav_chat" id="{{$entry->id}}">
                        <button class="flex justify-center items-center w-[34px] h-[34px] cursor-pointer rounded-full bg-white/5 text-heading border-none shadow-sm transition-all hover:scale-105 hover:-translate-y-1 hover:!shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" class="unselected">
                                <path d="M7.99794 11.8333L3.88327 13.9966L4.66927 9.41459L1.33594 6.16993L5.93594 5.50326L7.99327 1.33459L10.0506 5.50326L14.6506 6.16993L11.3173 9.41459L12.1033 13.9966L7.99794 11.8333Z" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <svg width="16" height="15" viewBox="0 0 16 15" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="selected">
								<path d="M7.99989 11.8333L3.88522 13.9966L4.67122 9.41459L1.33789 6.16993L5.93789 5.50326L7.99522 1.33459L10.0526 5.50326L14.6526 6.16993L11.3192 9.41459L12.1052 13.9966L7.99989 11.8333Z" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"></path>
							</svg>
                        </button>
                    </div>
                    @if ($entry->plan == 'premium')
                        <div class="absolute top-[10px] right-[20px]">
                            <div class="flex justify-center items-center bg-[#f3e2fd] h-[34px] rounded-[4px] p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21"
                                    fill="none">
                                    <g clip-path="url(#clip0_2737_4708)">
                                        <path
                                            d="M5.25 4.375H15.75L18.375 8.75L10.9375 17.0625C10.8805 17.1207 10.8124 17.1669 10.7373 17.1985C10.6622 17.2301 10.5815 17.2463 10.5 17.2463C10.4185 17.2463 10.3378 17.2301 10.2627 17.1985C10.1876 17.1669 10.1195 17.1207 10.0625 17.0625L2.625 8.75L5.25 4.375Z"
                                            stroke="#2C3E50" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path d="M8.75 10.5L7 8.57495L7.525 7.69995" stroke="#2C3E50" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_2737_4708">
                                            <rect width="21" height="21" fill="white" />
                                        </clipPath>
                                    </defs>
                                </svg>
                                <label class="text-[11px] font-medium color-[#000]">Premium</label>
                            </div>
                        </div>
                    @endif
                    <div class="flex flex-col justify-center text-center relative">
                        <div class="inline-flex items-center justify-center w-[128px] h-[128px] rounded-full mx-auto mb-6 transition-shadow text-[44px] font-medium overflow-hidden border-solid border-[6px] !border-white shadow-[0_1px_2px_rgba(0,0,0,0.07)] text-[rgba(0,0,0,0.65)] whitespace-nowrap overflow-ellipsis dark:!border-current group-hover:shadow-xl"
                            style="background: {{ $entry->color }};">
                            @if ($entry->slug === 'ai-chat-bot')
                                <img class="w-full h-full object-cover object-center" src="/assets/img/chat-default.jpg"
                                    alt="{{ __($entry->name) }}">
                            @elseif ($entry->image)
                                <img class="w-full h-full object-cover object-center" src="/{{ $entry->image }}"
                                    alt="{{ __($entry->name) }}">
                            @else
                                <span
                                    class="block w-full text-center whitespace-nowrap overflow-hidden overflow-ellipsis">{{ __($entry->short_name) }}</span>
                            @endif
                        </div>
                        <h3 class="mb-0 chat_name">{{ __($entry->name) }}</h3>
                        <p class="text-muted chat_description">{{ __($entry->description) }}</p>
                        <!-- link to the chat -->
                        <a href="{{ LaravelLocalization::localizeUrl(route('dashboard.user.openai.chat.chat', $entry->slug)) }}"
                            class="block w-full h-full absolute top-0 left-0 z-2"></a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

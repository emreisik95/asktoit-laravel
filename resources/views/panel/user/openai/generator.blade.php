@extends('panel.layout.app')
@section('title', $openai->title)

@section('content')
    <div class="page-header">
        <div class="container-xl">
            <div class="row g-2 items-center">
                <div class="col">
                    <a href="{{ LaravelLocalization::localizeUrl(route('dashboard.index')) }}"
                        class="flex items-center page-pretitle">
                        <svg class="!me-2 rtl:-scale-x-100" width="8" height="10" viewBox="0 0 6 10" fill="currentColor"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M4.45536 9.45539C4.52679 9.45539 4.60714 9.41968 4.66071 9.36611L5.10714 8.91968C5.16071 8.86611 5.19643 8.78575 5.19643 8.71432C5.19643 8.64289 5.16071 8.56254 5.10714 8.50896L1.59821 5.00004L5.10714 1.49111C5.16071 1.43753 5.19643 1.35718 5.19643 1.28575C5.19643 1.20539 5.16071 1.13396 5.10714 1.08039L4.66071 0.633963C4.60714 0.580392 4.52679 0.544678 4.45536 0.544678C4.38393 0.544678 4.30357 0.580392 4.25 0.633963L0.0892856 4.79468C0.0357141 4.84825 0 4.92861 0 5.00004C0 5.07146 0.0357141 5.15182 0.0892856 5.20539L4.25 9.36611C4.30357 9.41968 4.38393 9.45539 4.45536 9.45539Z" />
                        </svg>
                        {{ __('Back to dashboard') }}
                    </a>
                    <h2 class="page-title mb-2">
                        {{ __($openai->title) }}
                    </h2>
                    <div class="mt-3 page-pretitle">
                        {{ __($openai->description) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Page body -->
    <div class="page-body page-generator pt-6">
        <div class="container-xl">
            @if ($openai->type == 'image')
                @include('panel.user.openai.generator_components.generator_image')
            @elseif($openai->type == 'voiceover')
                @include('panel.user.openai.generator_components.generator_voiceover')
            @else
                @include('panel.user.openai.generator_components.generator_others')
            @endif
        </div>
    </div>
@endsection
@section('script')
    <script src="/assets/libs/tom-select/dist/js/tom-select.base.min.js?1674944402" defer></script>
    <script src="/assets/js/panel/openai_generator.js"></script>
    <script src="/assets/libs/fslightbox/index.js?1674944402" defer></script>
    <script src="/assets/libs/wavesurfer/wavesurfer.js"></script>
    <script src="/assets/js/panel/voiceover.js"></script>

    @if ($openai->type == 'voiceover')
        <script>
            function generateSpeech() {
                "use strict";

                document.getElementById("generate_speech_button").disabled = true;
                document.getElementById("generate_speech_button").innerHTML = magicai_localize.please_wait;

                var formData = new FormData();
                var speechData = [];
                formData.append('workbook_title', $('#workbook_title').val());

                let aContent = "";

                $('.speeches .speech').each(function() {
                    var data = {
                        voice: $(this).find('textarea').attr('data-voice'),
                        lang: $(this).find('textarea').attr('data-lang'),
                        language: $(this).find('textarea').attr('data-language'),
                        pace: $(this).find('textarea').attr('data-pace'),
                        platform: $(this).find('textarea').attr('data-platform'),
                        break: $(this).find('textarea').attr('data-break'),
                        voice_openai: $(this).find('textarea').attr('data-voiceopenai'),
                        model_openai: $(this).find('textarea').attr('data-modelopenai'),
                        content: $(this).find('textarea').val()
                    };
                    speechData.push(data);
                    aContent += data.content + "";
                });
                var jsonData = JSON.stringify(speechData);
                formData.append('speeches', jsonData);

                $.ajax({
                    type: "post",
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    },
                    url: "/dashboard/user/openai/generate-speech",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(data) {
                        toastr.success("{{ __('Generated') }}");
                        document.getElementById("generate_speech_button").disabled = false;
                        document.getElementById("generate_speech_button").innerHTML = "{{ __('Generate') }}";
                        $("#generator_sidebar_table").html(data.html2);

                        var audioElements = document.querySelectorAll('.data-audio');
                        audioElements.forEach(generateWaveForm);
                    },
                    error: function(data) {
                        var err = data.responseJSON.errors;
                        $.each(err, function(index, value) {
                            toastr.error(value);
                        });
                        document.getElementById("generate_speech_button").disabled = false;
                        // document.getElementById("generate_speech_button").innerHTML = "{{ __('Save') }}";
                    }
                });
                return false;
            }

            const openaiVoiceData = [
                {
                    value: "alloy",
                    label: "Alloy (Male)"
                }, {
                    value: "echo",
                    label: "Echo (Male)"
                }, {
                    value: "fable",
                    label: "Fable (Male)"
                }, {
                    value: "onyx",
                    label: "Onyx (Male)"
                }, {
                    value: "nova",
                    label: "Nova (Female)"
                }, {
                    value: "shimmer",
                    label: "Shimmer (Female)"
                }
            ];

            const allowedOpenAIList = [
                "af-ZA",
                "ar-XA",
                "eu-ES",
                "bg-BG",
                "ca-ES",
                "cs-CZ",
                "da-DK",
                "nl-BE",
                "en-US",
                "fi-FI",
                "fr-FR",
                "gl-ES",
                "de-DE",
                "el-GR",
                "he-IL",
                "hi-IN",
                "hu-HU",
                "is-IS",
                "id-ID",
                "it-IT",
                "ja-JP",
                "kn-IN",
                "ko-KR",
                "lv-LV",
                "ms-MY",
                "ml-IN",
                "nb-NO",
                "pl-PL",
                "pt-PT",
                "ro-RO",
                "ru-RU",
                "sr-RS",
                "sk-SK",
                "es-ES",
                "ta-IN",
                "th-TH",
                "tr-TR",
                "uk-UA",
                "vi-VN"
            ];

            const voicesData = {
                "af-ZA": [{
                    value: "af-ZA-Standard-A",
                    label: "Ayanda ({{ __('Female') }})"
                }],
                "ar-XA": [{
                        value: "ar-XA-Standard-A",
                        label: "Fatima ({{ __('Female') }})"
                    },
                    {
                        value: "ar-XA-Standard-B",
                        label: "Ahmed ({{ __('Male') }})"
                    },
                    {
                        value: "ar-XA-Standard-C",
                        label: "Mohammed ({{ __('Male') }})"
                    },
                    {
                        value: "ar-XA-Standard-D",
                        label: "Aisha ({{ __('Female') }})"
                    },
                    {
                        value: "ar-XA-Wavenet-A",
                        label: "Layla ({{ __('Female') }})"
                    },
                    {
                        value: "ar-XA-Wavenet-B",
                        label: "Ali ({{ __('Male') }})"
                    },
                    {
                        value: "ar-XA-Wavenet-C",
                        label: "Omar ({{ __('Male') }})"
                    },
                    {
                        value: "ar-XA-Wavenet-D",
                        label: "Zahra ({{ __('Female') }})"
                    }
                ],
                "eu-ES": [{
                    value: "eu-ES-Standard-A",
                    label: "Ane ({{ __('Female') }})"
                }],
                "bn-IN": [{
                        value: "bn-IN-Standard-A",
                        label: "Ananya ({{ __('Female') }})"
                    },
                    {
                        value: "bn-IN-Standard-B",
                        label: "Aryan ({{ __('Male') }})"
                    },
                    {
                        value: "bn-IN-Wavenet-A",
                        label: "Ishita ({{ __('Female') }})"
                    },
                    {
                        value: "bn-IN-Wavenet-B",
                        label: "Arry ({{ __('Male') }})"
                    }
                ],
                "bg-BG": [{
                    value: "bg-BG-Standard-A",
                    label: "Elena ({{ __('Female') }})"
                }],
                "ca-ES": [{
                    value: "ca-ES-Standard-A",
                    label: "Laia ({{ __('Female') }})"
                }],
                "yue-HK": [{
                        value: "yue-HK-Standard-A",
                        label: "Wing ({{ __('Female') }})"
                    },
                    {
                        value: "yue-HK-Standard-B",
                        label: "Ho ({{ __('Male') }})"
                    },
                    {
                        value: "yue-HK-Standard-C",
                        label: "Siu ({{ __('Female') }})"
                    },
                    {
                        value: "yue-HK-Standard-D",
                        label: "Lau ({{ __('Male') }})"
                    }
                ],
                "cs-CZ": [{
                        value: "cs-CZ-Standard-A",
                        label: "Tereza ({{ __('Female') }})"
                    },
                    {
                        value: "cs-CZ-Wavenet-A",
                        label: "Karolína ({{ __('Female') }})"
                    }
                ],
                "da-DK": [
                    //{value:"da-DK-Neural2-D", label: "Neural2 - FEMALE"},
                    //{value:"da-DK-Neural2-F", label: "Neural2 - MALE"},
                    {
                        value: "da-DK-Standard-A",
                        label: "Emma ({{ __('Female') }})"
                    },
                    {
                        value: "da-DK-Standard-A",
                        label: "Freja ({{ __('Female') }})"
                    },
                    {
                        value: "da-DK-Standard-A",
                        label: "Ida ({{ __('Female') }})"
                    },
                    {
                        value: "da-DK-Standard-C",
                        label: "Noah ({{ __('Male') }})"
                    },
                    {
                        value: "da-DK-Standard-D",
                        label: "Mathilde ({{ __('Female') }})"
                    },
                    {
                        value: "da-DK-Standard-E",
                        label: "Clara ({{ __('Female') }})"
                    },
                    {
                        value: "da-DK-Wavenet-A",
                        label: "Isabella ({{ __('Female') }})"
                    },
                    {
                        value: "da-DK-Wavenet-C",
                        label: "Lucas ({{ __('Male') }})"
                    },
                    {
                        value: "da-DK-Wavenet-D",
                        label: "Olivia ({{ __('Female') }})"
                    },
                    {
                        value: "da-DK-Wavenet-E",
                        label: "Emily ({{ __('Female') }})"
                    }
                ],
                "nl-BE": [{
                        value: "nl-BE-Standard-A",
                        label: "Emma ({{ __('Female') }})"
                    },
                    {
                        value: "nl-BE-Standard-B",
                        label: "Thomas ({{ __('Male') }})"
                    },
                    {
                        value: "nl-BE-Wavenet-A",
                        label: "Sophie ({{ __('Female') }})"
                    },
                    {
                        value: "nl-BE-Wavenet-B",
                        label: "Lucas ({{ __('Male') }})"
                    }
                ],
                "nl-NL": [{
                        value: "nl-NL-Standard-A",
                        label: "Emma ({{ __('Female') }})"
                    },
                    {
                        value: "nl-NL-Standard-B",
                        label: "Daan ({{ __('Male') }})"
                    },
                    {
                        value: "nl-NL-Standard-C",
                        label: "Luuk ({{ __('Male') }})"
                    },
                    {
                        value: "nl-NL-Standard-D",
                        label: "Lotte ({{ __('Female') }})"
                    },
                    {
                        value: "nl-NL-Standard-E",
                        label: "Sophie ({{ __('Female') }})"
                    },
                    {
                        value: "nl-NL-Wavenet-A",
                        label: "Mila ({{ __('Female') }})"
                    },
                    {
                        value: "nl-NL-Wavenet-B",
                        label: "Sem ({{ __('Male') }})"
                    },
                    {
                        value: "nl-NL-Wavenet-C",
                        label: "Stijn ({{ __('Male') }})"
                    },
                    {
                        value: "nl-NL-Wavenet-D",
                        label: "Fenna ({{ __('Female') }})"
                    },
                    {
                        value: "nl-NL-Wavenet-E",
                        label: "Eva ({{ __('Female') }})"
                    }
                ],
                "en-AU": [
                    //{value:"en-AU-Neural2-A", label: "Neural2 - FEMALE"},
                    //{value:"en-AU-Neural2-B", label: "Neural2 - MALE"},
                    //{value:"en-AU-Neural2-C", label: "Neural2 - FEMALE"},
                    //{value:"en-AU-Neural2-D", label: "Neural2 - MALE"},
                    {
                        value: "en-AU-News-E",
                        label: "Emma ({{ __('Female') }})"
                    },
                    {
                        value: "en-AU-News-F",
                        label: "Olivia ({{ __('Female') }})"
                    },
                    {
                        value: "en-AU-News-G",
                        label: "Liam ({{ __('Male') }})"
                    },
                    //{value:"en-AU-Polyglot-1", label: "Noah ({{ __('Male') }})"},
                    {
                        value: "en-AU-Standard-A",
                        label: "Charlotte ({{ __('Female') }})"
                    },
                    {
                        value: "en-AU-Standard-B",
                        label: "Oliver ({{ __('Male') }})"
                    },
                    {
                        value: "en-AU-Standard-C",
                        label: "Ava ({{ __('Female') }})"
                    },
                    {
                        value: "en-AU-Standard-D",
                        label: "Jack ({{ __('Male') }})"
                    },
                    {
                        value: "en-AU-Wavenet-A",
                        label: "Sophie ({{ __('Female') }})"
                    },
                    {
                        value: "en-AU-Wavenet-B",
                        label: "William ({{ __('Male') }})"
                    },
                    {
                        value: "en-AU-Wavenet-C",
                        label: "Amelia ({{ __('Female') }})"
                    },
                    {
                        value: "en-AU-Wavenet-D",
                        label: "Thomas ({{ __('Male') }})"
                    }
                ],
                "en-IN": [{
                        value: "en-IN-Standard-A",
                        label: "Aditi ({{ __('Female') }})"
                    },
                    {
                        value: "en-IN-Standard-B",
                        label: "Arjun ({{ __('Male') }})"
                    },
                    {
                        value: "en-IN-Standard-C",
                        label: "Rohan ({{ __('Male') }})"
                    },
                    {
                        value: "en-IN-Standard-D",
                        label: "Ananya ({{ __('Female') }})"
                    },
                    {
                        value: "en-IN-Wavenet-A",
                        label: "Alisha ({{ __('Female') }})"
                    },
                    {
                        value: "en-IN-Wavenet-B",
                        label: "Aryan ({{ __('Male') }})"
                    },
                    {
                        value: "en-IN-Wavenet-C",
                        label: "Kabir ({{ __('Male') }})"
                    },
                    {
                        value: "en-IN-Wavenet-D",
                        label: "Diya ({{ __('Female') }})"
                    }
                ],
                "en-GB": [
                    //{value:"en-GB-Neural2-A", label: "Neural2 - FEMALE"},
                    //{value:"en-GB-Neural2-B", label: "Neural2 - MALE"},
                    //{value:"en-GB-Neural2-C", label: "Neural2 - FEMALE"},
                    //{value:"en-GB-Neural2-D", label: "Neural2 - MALE"},
                    //{value:"en-GB-Neural2-F", label: "Neural2 - FEMALE"},
                    {
                        value: "en-GB-News-G",
                        label: "Amelia ({{ __('Female') }})"
                    },
                    {
                        value: "en-GB-News-H",
                        label: "Elise ({{ __('Female') }})"
                    },
                    {
                        value: "en-GB-News-I",
                        label: "Isabella ({{ __('Female') }})"
                    },
                    {
                        value: "en-GB-News-J",
                        label: "Jessica ({{ __('Female') }})"
                    },
                    {
                        value: "en-GB-News-K",
                        label: "Alexander ({{ __('Male') }})"
                    },
                    {
                        value: "en-GB-News-L",
                        label: "Benjamin ({{ __('Male') }})"
                    },
                    {
                        value: "en-GB-News-M",
                        label: "Charles ({{ __('Male') }})"
                    },
                    {
                        value: "en-GB-Standard-A",
                        label: "Emily ({{ __('Female') }})"
                    },
                    {
                        value: "en-GB-Standard-B",
                        label: "John ({{ __('Male') }})"
                    },
                    {
                        value: "en-GB-Standard-C",
                        label: "Mary ({{ __('Female') }})"
                    },
                    {
                        value: "en-GB-Standard-D",
                        label: "Peter ({{ __('Male') }})"
                    },
                    {
                        value: "en-GB-Standard-F",
                        label: "Sarah ({{ __('Female') }})"
                    },
                    {
                        value: "en-GB-Wavenet-A",
                        label: "Ava ({{ __('Female') }})"
                    },
                    {
                        value: "en-GB-Wavenet-B",
                        label: "David ({{ __('Male') }})"
                    },
                    {
                        value: "en-GB-Wavenet-C",
                        label: "Emily ({{ __('Female') }})"
                    },
                    {
                        value: "en-GB-Wavenet-D",
                        label: "James ({{ __('Male') }})"
                    },
                    {
                        value: "en-GB-Wavenet-F",
                        label: "Sophie ({{ __('Female') }})"
                    }
                ],
                "en-US": [
                    //{value:"en-US-Neural2-A", label: "Neural2 - MALE"},
                    //{value:"en-US-Neural2-C", label: "Neural2 - FEMALE"},
                    //{value:"en-US-Neural2-D", label: "Neural2 - MALE"},
                    //{value:"en-US-Neural2-E", label: "Neural2 - FEMALE"},
                    //{value:"en-US-Neural2-F", label: "Neural2 - FEMALE"},
                    //{value:"en-US-Neural2-G", label: "Neural2 - FEMALE"},
                    //{value:"en-US-Neural2-H", label: "Neural2 - FEMALE"},
                    //{value:"en-US-Neural2-I", label: "Neural2 - MALE"},
                    //{value:"en-US-Neural2-J", label: "Neural2 - MALE"},
                    {
                        value: "en-US-News-K",
                        label: "Lily ({{ __('Female') }})"
                    },
                    {
                        value: "en-US-News-L",
                        label: "Olivia ({{ __('Female') }})"
                    },
                    {
                        value: "en-US-News-M",
                        label: "Noah ({{ __('Male') }})"
                    },
                    {
                        value: "en-US-News-N",
                        label: "Oliver ({{ __('Male') }})"
                    },
                    //{value:"en-US-Polyglot-1", label:"John ({{ __('Male') }})"},
                    {
                        value: "en-US-Standard-A",
                        label: "Michael ({{ __('Male') }})"
                    },
                    {
                        value: "en-US-Standard-B",
                        label: "David ({{ __('Male') }})"
                    },
                    {
                        value: "en-US-Standard-C",
                        label: "Emma ({{ __('Female') }})"
                    },
                    {
                        value: "en-US-Standard-D",
                        label: "William ({{ __('Male') }})"
                    },
                    {
                        value: "en-US-Standard-E",
                        label: "Ava ({{ __('Female') }})"
                    },
                    {
                        value: "en-US-Standard-F",
                        label: "Sophia ({{ __('Female') }})"
                    },
                    {
                        value: "en-US-Standard-G",
                        label: "Isabella ({{ __('Female') }})"
                    },
                    {
                        value: "en-US-Standard-H",
                        label: "Charlotte ({{ __('Female') }})"
                    },
                    {
                        value: "en-US-Standard-I",
                        label: "James ({{ __('Male') }})"
                    },
                    {
                        value: "en-US-Standard-J",
                        label: "Lucas ({{ __('Male') }})"
                    },
                    {
                        value: "en-US-Studio-M",
                        label: "Benjamin ({{ __('Male') }})"
                    },
                    {
                        value: "en-US-Studio-O",
                        label: "Eleanor ({{ __('Female') }})"
                    },
                    {
                        value: "en-US-Wavenet-A",
                        label: "Alexander ({{ __('Male') }})"
                    },
                    {
                        value: "en-US-Wavenet-B",
                        label: "Benjamin ({{ __('Male') }})"
                    },
                    {
                        value: "en-US-Wavenet-C",
                        label: "Emily ({{ __('Female') }})"
                    },
                    {
                        value: "en-US-Wavenet-D",
                        label: "James ({{ __('Male') }})"
                    },
                    {
                        value: "en-US-Wavenet-E",
                        label: "Ava ({{ __('Female') }})"
                    },
                    {
                        value: "en-US-Wavenet-F",
                        label: "Sophia ({{ __('Female') }})"
                    },
                    {
                        value: "en-US-Wavenet-G",
                        label: "Isabella ({{ __('Female') }})"
                    },
                    {
                        value: "en-US-Wavenet-H",
                        label: "Charlotte ({{ __('Female') }})"
                    },
                    {
                        value: "en-US-Wavenet-I",
                        label: "Alexander ({{ __('Male') }})"
                    },
                    {
                        value: "en-US-Wavenet-J",
                        label: "Lucas ({{ __('Male') }})"
                    }
                ],
                "fil-PH": [{
                        value: "fil-PH-Standard-A",
                        label: "Maria ({{ __('Female') }})"
                    },
                    {
                        value: "fil-PH-Standard-B",
                        label: "Juana ({{ __('Female') }})"
                    },
                    {
                        value: "fil-PH-Standard-C",
                        label: "Juan ({{ __('Male') }})"
                    },
                    {
                        value: "fil-PH-Standard-D",
                        label: "Pedro ({{ __('Male') }})"
                    },
                    {
                        value: "fil-PH-Wavenet-A",
                        label: "Maria ({{ __('Female') }})"
                    },
                    {
                        value: "fil-PH-Wavenet-B",
                        label: "Juana ({{ __('Female') }})"
                    },
                    {
                        value: "fil-PH-Wavenet-C",
                        label: "Juan ({{ __('Male') }})"
                    },
                    {
                        value: "fil-PH-Wavenet-D",
                        label: "Pedro ({{ __('Male') }})"
                    }
                    //{value:"fil-ph-Neural2-A", label: "Neural2 - FEMALE"},
                    //{value:"fil-ph-Neural2-D", label: "Neural2 - MALE"}
                ],
                "fi-FI": [{
                        value: "fi-FI-Standard-A",
                        label: "Sofia ({{ __('Female') }})"
                    },
                    {
                        value: "fi-FI-Wavenet-A",
                        label: "Sofianna ({{ __('Female') }})"
                    }
                ],
                "fr-CA": [
                    //{value:"fr-CA-Neural2-A", label: "Neural2 - FEMALE"},
                    //{value:"fr-CA-Neural2-B", label: "Neural2 - MALE"},
                    //{value:"fr-CA-Neural2-C", label: "Neural2 - FEMALE"},
                    //{value:"fr-CA-Neural2-D", label: "Neural2 - MALE"},
                    {
                        value: "fr-CA-Standard-A",
                        label: "Emma ({{ __('Female') }})"
                    },
                    {
                        value: "fr-CA-Standard-B",
                        label: "Jean ({{ __('Male') }})"
                    },
                    {
                        value: "fr-CA-Standard-C",
                        label: "Gabrielle ({{ __('Female') }})"
                    },
                    {
                        value: "fr-CA-Standard-D",
                        label: "Thomas ({{ __('Male') }})"
                    },
                    {
                        value: "fr-CA-Wavenet-A",
                        label: "Amelie ({{ __('Female') }})"
                    },
                    {
                        value: "fr-CA-Wavenet-B",
                        label: "Antoine ({{ __('Male') }})"
                    },
                    {
                        value: "fr-CA-Wavenet-C",
                        label: "Gabrielle ({{ __('Female') }})"
                    },
                    {
                        value: "fr-CA-Wavenet-D",
                        label: "Thomas ({{ __('Male') }})"
                    }
                ],
                "fr-FR": [
                    //{value:"fr-FR-Neural2-A", label: "Neural2 - FEMALE"},
                    //{value:"fr-FR-Neural2-B", label: "Neural2 - MALE"},
                    //{value:"fr-FR-Neural2-C", label: "Neural2 - FEMALE"},
                    //{value:"fr-FR-Neural2-D", label: "Neural2 - MALE"},
                    //{value:"fr-FR-Neural2-E", label: "Neural2 - FEMALE"},
                    //{value:"fr-FR-Polyglot-1", label:"Jean ({{ __('Male') }})"},
                    {
                        value: "fr-FR-Standard-A",
                        label: "Marie ({{ __('Female') }})"
                    },
                    {
                        value: "fr-FR-Standard-B",
                        label: "Pierre ({{ __('Male') }})"
                    },
                    {
                        value: "fr-FR-Standard-C",
                        label: "Sophie ({{ __('Female') }})"
                    },
                    {
                        value: "fr-FR-Standard-D",
                        label: "Paul ({{ __('Male') }})"
                    },
                    {
                        value: "fr-FR-Standard-E",
                        label: "Julie ({{ __('Female') }})"
                    },
                    {
                        value: "fr-FR-Wavenet-A",
                        label: "Elise ({{ __('Female') }})"
                    },
                    {
                        value: "fr-FR-Wavenet-B",
                        label: "Nicolas ({{ __('Male') }})"
                    },
                    {
                        value: "fr-FR-Wavenet-C",
                        label: "Clara ({{ __('Female') }})"
                    },
                    {
                        value: "fr-FR-Wavenet-D",
                        label: "Antoine ({{ __('Male') }})"
                    },
                    {
                        value: "fr-FR-Wavenet-E",
                        label: "Amelie ({{ __('Female') }})"
                    }
                ],
                "gl-ES": [{
                    value: "gl-ES-Standard-A",
                    label: "Ana ({{ __('Female') }})"
                }],
                "de-DE": [
                    //{value:"de-DE-Neural2-B", label: "Neural2 - MALE"},
                    //{value:"de-DE-Neural2-C", label: "Neural2 - FEMALE"},
                    //{value:"de-DE-Neural2-D", label: "Neural2 - MALE"},
                    //{value:"de-DE-Neural2-F", label: "Neural2 - FEMALE"},
                    //{value:"de-DE-Polyglot-1", label:"Johannes ({{ __('Male') }})"},
                    {
                        value: "de-DE-Standard-A",
                        label: "Anna ({{ __('Female') }})"
                    },
                    {
                        value: "de-DE-Standard-B",
                        label: "Max ({{ __('Male') }})"
                    },
                    {
                        value: "de-DE-Standard-C",
                        label: "Sophia ({{ __('Female') }})"
                    },
                    {
                        value: "de-DE-Standard-D",
                        label: "Paul ({{ __('Male') }})"
                    },
                    {
                        value: "de-DE-Standard-E",
                        label: "Erik ({{ __('Male') }})"
                    },
                    {
                        value: "de-DE-Standard-F",
                        label: "Lina ({{ __('Female') }})"
                    },
                    {
                        value: "de-DE-Wavenet-A",
                        label: "Eva ({{ __('Female') }})"
                    },
                    {
                        value: "de-DE-Wavenet-B",
                        label: "Felix ({{ __('Male') }})"
                    },
                    {
                        value: "de-DE-Wavenet-C",
                        label: "Emma ({{ __('Female') }})"
                    },
                    {
                        value: "de-DE-Wavenet-D",
                        label: "Lukas ({{ __('Male') }})"
                    },
                    {
                        value: "de-DE-Wavenet-E",
                        label: "Nico ({{ __('Male') }})"
                    },
                    {
                        value: "de-DE-Wavenet-F",
                        label: "Mia ({{ __('Female') }})"
                    }
                ],
                "el-GR": [{
                        value: "el-GR-Standard-A",
                        label: "Ελένη ({{ __('Female') }})"
                    },
                    {
                        value: "el-GR-Wavenet-A",
                        label: "Ελένη ({{ __('Female') }})"
                    }
                ],
                "gu-IN": [{
                        value: "gu-IN-Standard-A",
                        label: "દિવ્યા ({{ __('Female') }})"
                    },
                    {
                        value: "gu-IN-Standard-B",
                        label: "કિશોર ({{ __('Male') }})"
                    },
                    {
                        value: "gu-IN-Wavenet-A",
                        label: "દિવ્યા ({{ __('Female') }})"
                    },
                    {
                        value: "gu-IN-Wavenet-B",
                        label: "કિશોર ({{ __('Male') }})"
                    }
                ],
                "he-IL": [{
                        value: "he-IL-Standard-A",
                        label: "Tamar ({{ __('Female') }})"
                    },
                    {
                        value: "he-IL-Standard-B",
                        label: "David ({{ __('Male') }})"
                    },
                    {
                        value: "he-IL-Standard-C",
                        label: "Michal ({{ __('Female') }})"
                    },
                    {
                        value: "he-IL-Standard-D",
                        label: "Jonathan ({{ __('Male') }})"
                    },
                    {
                        value: "he-IL-Wavenet-A",
                        label: "Yael ({{ __('Female') }})"
                    },
                    {
                        value: "he-IL-Wavenet-B",
                        label: "Eli ({{ __('Male') }})"
                    },
                    {
                        value: "he-IL-Wavenet-C",
                        label: "Abigail ({{ __('Female') }})"
                    },
                    {
                        value: "he-IL-Wavenet-D",
                        label: "Alex ({{ __('Male') }})"
                    }
                ],
                "hi-IN": [
                    //{value:"hi-IN-Neural2-A", label: "Neural2 - FEMALE"},
                    //{value:"hi-IN-Neural2-B", label: "Neural2 - MALE"},
                    //{value:"hi-IN-Neural2-C", label: "Neural2 - MALE"},
                    //{value:"hi-IN-Neural2-D", label: "Neural2 - FEMALE"},
                    {
                        value: "hi-IN-Standard-A",
                        label: "Aditi ({{ __('Female') }})"
                    },
                    {
                        value: "hi-IN-Standard-B",
                        label: "Abhishek ({{ __('Male') }})"
                    },
                    {
                        value: "hi-IN-Standard-C",
                        label: "Aditya ({{ __('Male') }})"
                    },
                    {
                        value: "hi-IN-Standard-D",
                        label: "Anjali ({{ __('Female') }})"
                    },
                    {
                        value: "hi-IN-Wavenet-A",
                        label: "Kiara ({{ __('Female') }})"
                    },
                    {
                        value: "hi-IN-Wavenet-B",
                        label: "Rohan ({{ __('Male') }})"
                    },
                    {
                        value: "hi-IN-Wavenet-C",
                        label: "Rishabh ({{ __('Male') }})"
                    },
                    {
                        value: "hi-IN-Wavenet-D",
                        label: "Srishti ({{ __('Female') }})"
                    }
                ],
                "hu-HU": [{
                        value: "hu-HU-Standard-A",
                        label: "Eszter ({{ __('Female') }})"
                    },
                    {
                        value: "hu-HU-Wavenet-A",
                        label: "Lilla ({{ __('Female') }})"
                    }
                ],
                "is-IS": [{
                    value: "is-IS-Standard-A",
                    label: "Guðrún ({{ __('Female') }})"
                }],
                "id-ID": [{
                        value: "id-ID-Standard-A",
                        label: "Amelia ({{ __('Female') }})"
                    },
                    {
                        value: "id-ID-Standard-B",
                        label: "Fajar ({{ __('Male') }})"
                    },
                    {
                        value: "id-ID-Standard-C",
                        label: "Galih ({{ __('Male') }})"
                    },
                    {
                        value: "id-ID-Standard-D",
                        label: "Kiara ({{ __('Female') }})"
                    },
                    {
                        value: "id-ID-Wavenet-A",
                        label: "Nadia ({{ __('Female') }})"
                    },
                    {
                        value: "id-ID-Wavenet-B",
                        label: "Reza ({{ __('Male') }})"
                    },
                    {
                        value: "id-ID-Wavenet-C",
                        label: "Satria ({{ __('Male') }})"
                    },
                    {
                        value: "id-ID-Wavenet-D",
                        label: "Vania ({{ __('Female') }})"
                    }
                ],
                "it-IT": [
                    //{value:"it-IT-Neural2-A", label: "Neural2 - FEMALE"},
                    //{value:"it-IT-Neural2-C", label: "Neural2 - MALE"},
                    {
                        value: "it-IT-Standard-A",
                        label: "Chiara ({{ __('Female') }})"
                    },
                    {
                        value: "it-IT-Standard-B",
                        label: "Elisa ({{ __('Female') }})"
                    },
                    {
                        value: "it-IT-Standard-C",
                        label: "Matteo ({{ __('Male') }})"
                    },
                    {
                        value: "it-IT-Standard-D",
                        label: "Riccardo ({{ __('Male') }})"
                    },
                    {
                        value: "it-IT-Wavenet-A",
                        label: "Valentina ({{ __('Female') }})"
                    },
                    {
                        value: "it-IT-Wavenet-B",
                        label: "Vittoria ({{ __('Female') }})"
                    },
                    {
                        value: "it-IT-Wavenet-C",
                        label: "Andrea ({{ __('Male') }})"
                    },
                    {
                        value: "it-IT-Wavenet-D",
                        label: "Luca ({{ __('Male') }})"
                    }
                ],
                "ja-JP": [
                    //{value:"ja-JP-Neural2-B", label: "Neural2 - FEMALE"},
                    //{value:"ja-JP-Neural2-C", label: "Neural2 - MALE"},
                    //{value:"ja-JP-Neural2-D", label: "Neural2 - MALE"},
                    {
                        value: "ja-JP-Standard-A",
                        label: "Akane ({{ __('Female') }})"
                    },
                    {
                        value: "ja-JP-Standard-B",
                        label: "Emi ({{ __('Female') }})"
                    },
                    {
                        value: "ja-JP-Standard-C",
                        label: "Daisuke ({{ __('Male') }})"
                    },
                    {
                        value: "ja-JP-Standard-D",
                        label: "Kento ({{ __('Male') }})"
                    },
                    {
                        value: "ja-JP-Wavenet-A",
                        label: "Haruka ({{ __('Female') }})"
                    },
                    {
                        value: "ja-JP-Wavenet-B",
                        label: "Rin ({{ __('Female') }})"
                    },
                    {
                        value: "ja-JP-Wavenet-C",
                        label: "Shun ({{ __('Male') }})"
                    },
                    {
                        value: "ja-JP-Wavenet-D",
                        label: "Yuta ({{ __('Male') }})"
                    }
                ],
                "kn-IN": [{
                        value: "kn-IN-Standard-A",
                        label: "Dhanya ({{ __('Female') }})"
                    },
                    {
                        value: "kn-IN-Standard-B",
                        label: "Keerthi ({{ __('Male') }})"
                    },
                    {
                        value: "kn-IN-Wavenet-A",
                        label: "Meena ({{ __('Female') }})"
                    },
                    {
                        value: "kn-IN-Wavenet-B",
                        label: "Nandini ({{ __('Male') }})"
                    }
                ],
                "ko-KR": [
                    //{value:"ko-KR-Neural2-A", label: "Neural2 - FEMALE"},
                    //{value:"ko-KR-Neural2-B", label: "Neural2 - FEMALE"},
                    //{value:"ko-KR-Neural2-C", label: "Neural2 - MALE"},
                    {
                        value: "ko-KR-Standard-A",
                        label: "So-young ({{ __('Female') }})"
                    },
                    {
                        value: "ko-KR-Standard-B",
                        label: "Se-yeon ({{ __('Female') }})"
                    },
                    {
                        value: "ko-KR-Standard-C",
                        label: "Min-soo ({{ __('Male') }})"
                    },
                    {
                        value: "ko-KR-Standard-D",
                        label: "Seung-woo ({{ __('Male') }})"
                    },
                    {
                        value: "ko-KR-Wavenet-A",
                        label: "Ji-soo ({{ __('Female') }})"
                    },
                    {
                        value: "ko-KR-Wavenet-B",
                        label: "Yoon-a ({{ __('Female') }})"
                    },
                    {
                        value: "ko-KR-Wavenet-C",
                        label: "Tae-hyun ({{ __('Male') }})"
                    },
                    {
                        value: "ko-KR-Wavenet-D",
                        label: "Jun-ho ({{ __('Male') }})"
                    }
                ],
                "lv-LV": [{
                    value: "lv-LV-Standard-A",
                    label: "Raivis ({{ __('Male') }})"
                }],
                "lv-LT": [{
                    value: "lv-LT-Standard-A",
                    label: "Raivis ({{ __('Male') }})"
                }],
                "ms-MY": [{
                        value: "ms-MY-Standard-A",
                        label: "Amira ({{ __('Female') }})"
                    },
                    {
                        value: "ms-MY-Standard-B",
                        label: "Danial ({{ __('Male') }})"
                    },
                    {
                        value: "ms-MY-Standard-C",
                        label: "Eira ({{ __('Female') }})"
                    },
                    {
                        value: "ms-MY-Standard-D",
                        label: "Farhan ({{ __('Male') }})"
                    },
                    {
                        value: "ms-MY-Wavenet-A",
                        label: "Hana ({{ __('Female') }})"
                    },
                    {
                        value: "ms-MY-Wavenet-B",
                        label: "Irfan ({{ __('Male') }})"
                    },
                    {
                        value: "ms-MY-Wavenet-C",
                        label: "Janna ({{ __('Female') }})"
                    },
                    {
                        value: "ms-MY-Wavenet-D",
                        label: "Khairul ({{ __('Male') }})"
                    }
                ],
                "ml-IN": [{
                        value: "ml-IN-Standard-A",
                        label: "Aishwarya ({{ __('Female') }})"
                    },
                    {
                        value: "ml-IN-Standard-B",
                        label: "Dhruv ({{ __('Male') }})"
                    },
                    {
                        value: "ml-IN-Wavenet-A",
                        label: "Deepthi ({{ __('Female') }})"
                    },
                    {
                        value: "ml-IN-Wavenet-B",
                        label: "Gautam ({{ __('Male') }})"
                    },
                    {
                        value: "ml-IN-Wavenet-C",
                        label: "Isha ({{ __('Female') }})"
                    },
                    {
                        value: "ml-IN-Wavenet-D",
                        label: "Kabir ({{ __('Male') }})"
                    }
                ],
                "cmn-CN": [{
                        value: "cmn-CN-Standard-A",
                        label: "Xiaomei ({{ __('Female') }})"
                    },
                    {
                        value: "cmn-CN-Standard-B",
                        label: "Lijun ({{ __('Male') }})"
                    },
                    {
                        value: "cmn-CN-Standard-C",
                        label: "Minghao ({{ __('Male') }})"
                    },
                    {
                        value: "cmn-CN-Standard-D",
                        label: "Yingying ({{ __('Female') }})"
                    },
                    {
                        value: "cmn-CN-Wavenet-A",
                        label: "Shanshan ({{ __('Female') }})"
                    },
                    {
                        value: "cmn-CN-Wavenet-B",
                        label: "Chenchen ({{ __('Male') }})"
                    },
                    {
                        value: "cmn-CN-Wavenet-C",
                        label: "Jiahao ({{ __('Male') }})"
                    },
                    {
                        value: "cmn-CN-Wavenet-D",
                        label: "Yueyu ({{ __('Female') }})"
                    }
                ],
                "cmn-TW": [{
                        value: "cmn-TW-Standard-A",
                        label: "Jingwen ({{ __('Female') }})"
                    },
                    {
                        value: "cmn-TW-Standard-B",
                        label: "Jinghao ({{ __('Male') }})"
                    },
                    {
                        value: "cmn-TW-Standard-C",
                        label: "Tingting ({{ __('Female') }})"
                    },
                    {
                        value: "cmn-TW-Wavenet-A",
                        label: "Yunyun ({{ __('Female') }})"
                    },
                    {
                        value: "cmn-TW-Wavenet-B",
                        label: "Zhenghao ({{ __('Male') }})"
                    },
                    {
                        value: "cmn-TW-Wavenet-C",
                        label: "Yuehan ({{ __('Female') }})"
                    }
                ],
                "mr-IN": [{
                        value: "mr-IN-Standard-A",
                        label: "Anjali ({{ __('Female') }})"
                    },
                    {
                        value: "mr-IN-Standard-B",
                        label: "Aditya ({{ __('Male') }})"
                    },
                    {
                        value: "mr-IN-Standard-C",
                        label: "Dipti ({{ __('Female') }})"
                    },
                    {
                        value: "mr-IN-Wavenet-A",
                        label: "Gauri ({{ __('Female') }})"
                    },
                    {
                        value: "mr-IN-Wavenet-B",
                        label: "Harsh ({{ __('Male') }})"
                    },
                    {
                        value: "mr-IN-Wavenet-C",
                        label: "Ishita ({{ __('Female') }})"
                    }
                ],
                "nb-NO": [{
                        value: "nb-NO-Standard-A",
                        label: "Ingrid ({{ __('Female') }})"
                    },
                    {
                        value: "nb-NO-Standard-B",
                        label: "Jonas ({{ __('Male') }})"
                    },
                    {
                        value: "nb-NO-Standard-C",
                        label: "Marit ({{ __('Female') }})"
                    },
                    {
                        value: "nb-NO-Standard-D",
                        label: "Olav ({{ __('Male') }})"
                    },
                    {
                        value: "nb-NO-Standard-E",
                        label: "Silje ({{ __('Female') }})"
                    },
                    {
                        value: "nb-NO-Wavenet-A",
                        label: "Astrid ({{ __('Female') }})"
                    },
                    {
                        value: "nb-NO-Wavenet-B",
                        label: "Eirik ({{ __('Male') }})"
                    },
                    {
                        value: "nb-NO-Wavenet-C",
                        label: "Inger ({{ __('Female') }})"
                    },
                    {
                        value: "nb-NO-Wavenet-D",
                        label: "Kristian ({{ __('Male') }})"
                    },
                    {
                        value: "nb-NO-Wavenet-E",
                        label: "Trine ({{ __('Female') }})"
                    }
                ],
                "pl-PL": [{
                        value: "pl-PL-Standard-A",
                        label: "Agata ({{ __('Female') }})"
                    },
                    {
                        value: "pl-PL-Standard-B",
                        label: "Bartosz ({{ __('Male') }})"
                    },
                    {
                        value: "pl-PL-Standard-C",
                        label: "Kamil ({{ __('Male') }})"
                    },
                    {
                        value: "pl-PL-Standard-D",
                        label: "Julia ({{ __('Female') }})"
                    },
                    {
                        value: "pl-PL-Standard-E",
                        label: "Magdalena ({{ __('Female') }})"
                    },
                    {
                        value: "pl-PL-Wavenet-A",
                        label: "Natalia ({{ __('Female') }})"
                    },
                    {
                        value: "pl-PL-Wavenet-B",
                        label: "Paweł ({{ __('Male') }})"
                    },
                    {
                        value: "pl-PL-Wavenet-C",
                        label: "Tomasz ({{ __('Male') }})"
                    },
                    {
                        value: "pl-PL-Wavenet-D",
                        label: "Zofia ({{ __('Female') }})"
                    },
                    {
                        value: "pl-PL-Wavenet-E",
                        label: "Wiktoria ({{ __('Female') }})"
                    }
                ],
                "pt-BR": [
                    //{value:"pt-BR-Neural2-A", label: "Neural2 - FEMALE"},
                    //{value:"pt-BR-Neural2-B", label: "Neural2 - MALE"},
                    //{value:"pt-BR-Neural2-C", label: "Neural2 - FEMALE"},
                    {
                        value: "pt-BR-Standard-A",
                        label: "Ana ({{ __('Female') }})"
                    },
                    {
                        value: "pt-BR-Standard-B",
                        label: "Carlos ({{ __('Male') }})"
                    },
                    {
                        value: "pt-BR-Standard-C",
                        label: "Maria ({{ __('Female') }})"
                    },
                    {
                        value: "pt-BR-Wavenet-A",
                        label: "Julia ({{ __('Female') }})"
                    },
                    {
                        value: "pt-BR-Wavenet-B",
                        label: "João ({{ __('Male') }})"
                    },
                    {
                        value: "pt-BR-Wavenet-C",
                        label: "Fernanda ({{ __('Female') }})"
                    }
                ],
                "pt-PT": [{
                        value: "pt-PT-Standard-A",
                        label: "Maria ({{ __('Female') }})"
                    },
                    {
                        value: "pt-PT-Standard-B",
                        label: "José ({{ __('Male') }})"
                    },
                    {
                        value: "pt-PT-Standard-C",
                        label: "Luís ({{ __('Male') }})"
                    },
                    {
                        value: "pt-PT-Standard-D",
                        label: "Ana ({{ __('Female') }})"
                    },
                    {
                        value: "pt-PT-Wavenet-A",
                        label: "Catarina ({{ __('Female') }})"
                    },
                    {
                        value: "pt-PT-Wavenet-B",
                        label: "Miguel ({{ __('Male') }})"
                    },
                    {
                        value: "pt-PT-Wavenet-C",
                        label: "João ({{ __('Male') }})"
                    },
                    {
                        value: "pt-PT-Wavenet-D",
                        label: "Marta ({{ __('Female') }})"
                    }
                ],
                "pa-IN": [{
                        value: "pa-IN-Standard-A",
                        label: "Harpreet ({{ __('Female') }})"
                    },
                    {
                        value: "pa-IN-Standard-B",
                        label: "Gurpreet ({{ __('Male') }})"
                    },
                    {
                        value: "pa-IN-Standard-C",
                        label: "Jasmine ({{ __('Female') }})"
                    },
                    {
                        value: "pa-IN-Standard-D",
                        label: "Rahul ({{ __('Male') }})"
                    },
                    {
                        value: "pa-IN-Wavenet-A",
                        label: "Simran ({{ __('Female') }})"
                    },
                    {
                        value: "pa-IN-Wavenet-B",
                        label: "Amardeep ({{ __('Male') }})"
                    },
                    {
                        value: "pa-IN-Wavenet-C",
                        label: "Kiran ({{ __('Female') }})"
                    },
                    {
                        value: "pa-IN-Wavenet-D",
                        label: "Raj ({{ __('Male') }})"
                    }
                ],
                "ro-RO": [{
                        value: "ro-RO-Standard-A",
                        label: "Maria ({{ __('Female') }})"
                    },
                    {
                        value: "ro-RO-Wavenet-A",
                        label: "Ioana ({{ __('Female') }})"
                    }
                ],
                "ru-RU": [{
                        value: "ru-RU-Standard-A",
                        label: "Anastasia"
                    },
                    {
                        value: "ru-RU-Standard-B",
                        label: "Alexander"
                    },
                    {
                        value: "ru-RU-Standard-C",
                        label: "Elizabeth"
                    },
                    {
                        value: "ru-RU-Standard-D",
                        label: "Michael"
                    },
                    {
                        value: "ru-RU-Standard-E",
                        label: "Victoria"
                    },
                    {
                        value: "ru-RU-Wavenet-A",
                        label: "Daria"
                    },
                    {
                        value: "ru-RU-Wavenet-B",
                        label: "Dmitry"
                    },
                    {
                        value: "ru-RU-Wavenet-C",
                        label: "Kristina"
                    },
                    {
                        value: "ru-RU-Wavenet-D",
                        label: "Ivan"
                    },
                    {
                        value: "ru-RU-Wavenet-E",
                        label: "Sophia"
                    }
                ],
                "sr-RS": [{
                    value: "sr-RS-Standard-A",
                    label: "Ana"
                }],
                "sk-SK": [{
                        value: "sk-SK-Standard-A",
                        label: "Mária ({{ __('Female') }})"
                    },
                    {
                        value: "sk-SK-Wavenet-A",
                        label: "Zuzana ({{ __('Female') }})"
                    }
                ],
                "es-ES": [
                    //{value:"es-ES-Neural2-A", label: "Neural2 - FEMALE"},
                    //{value:"es-ES-Neural2-B", label: "Neural2 - MALE"},
                    //{value:"es-ES-Neural2-C", label: "Neural2 - FEMALE"},
                    //{value:"es-ES-Neural2-D", label: "Neural2 - FEMALE"},
                    //{value:"es-ES-Neural2-E", label: "Neural2 - FEMALE"},
                    //{value:"es-ES-Neural2-F", label: "Neural2 - MALE"},
                    //{value: "es-ES-Polyglot-1", label: "Juan ({{ __('Male') }})"},
                    {
                        value: "es-ES-Standard-A",
                        label: "María ({{ __('Female') }})"
                    },
                    {
                        value: "es-ES-Standard-B",
                        label: "José ({{ __('Male') }})"
                    },
                    {
                        value: "es-ES-Standard-C",
                        label: "Ana ({{ __('Female') }})"
                    },
                    {
                        value: "es-ES-Standard-D",
                        label: "Isabel ({{ __('Female') }})"
                    },
                    {
                        value: "es-ES-Wavenet-B",
                        label: "Pedro ({{ __('Male') }})"
                    },
                    {
                        value: "es-ES-Wavenet-C",
                        label: "Laura ({{ __('Female') }})"
                    },
                    {
                        value: "es-ES-Wavenet-D",
                        label: "Julia ({{ __('Female') }})"
                    }

                ],
                "es-US": [
                    //{value:"es-US-Neural2-A", label: "Neural2 - FEMALE"},
                    //{value:"es-US-Neural2-B", label: "Neural2 - MALE"},
                    //{value:"es-US-Neural2-C", label: "Neural2 - MALE"},
                    {
                        value: "es-US-News-D",
                        label: "Diego ({{ __('Male') }})"
                    },
                    {
                        value: "es-US-News-E",
                        label: "Eduardo ({{ __('Male') }})"
                    },
                    {
                        value: "es-US-News-F",
                        label: "Fátima ({{ __('Female') }})"
                    },
                    {
                        value: "es-US-News-G",
                        label: "Gabriela ({{ __('Female') }})"
                    },
                    //{value:"es-US-Polyglot-1", label: "Juan ({{ __('Male') }})"},
                    {
                        value: "es-US-Standard-A",
                        label: "Ana ({{ __('Female') }})"
                    },
                    {
                        value: "es-US-Standard-B",
                        label: "José ({{ __('Male') }})"
                    },
                    {
                        value: "es-US-Standard-C",
                        label: "Carlos ({{ __('Male') }})"
                    },
                    {
                        value: "es-US-Studio-B",
                        label: "Miguel ({{ __('Male') }})"
                    },
                    {
                        value: "es-US-Wavenet-A",
                        label: "Laura ({{ __('Female') }})"
                    },
                    {
                        value: "es-US-Wavenet-B",
                        label: "Pedro ({{ __('Male') }})"
                    },
                    {
                        value: "es-US-Wavenet-C",
                        label: "Pablo ({{ __('Male') }})"
                    }
                ],
                "sv-SE": [{
                        value: "sv-SE-Standard-A",
                        label: "Ebba ({{ __('Female') }})"
                    },
                    {
                        value: "sv-SE-Standard-B",
                        label: "Saga ({{ __('Female') }})"
                    },
                    {
                        value: "sv-SE-Standard-C",
                        label: "Linnea ({{ __('Female') }})"
                    },
                    {
                        value: "sv-SE-Standard-D",
                        label: "Erik ({{ __('Male') }})"
                    },
                    {
                        value: "sv-SE-Standard-E",
                        label: "Anton ({{ __('Male') }})"
                    },
                    {
                        value: "sv-SE-Wavenet-A",
                        label: "Astrid ({{ __('Female') }})"
                    },
                    {
                        value: "sv-SE-Wavenet-B",
                        label: "Elin ({{ __('Female') }})"
                    },
                    {
                        value: "sv-SE-Wavenet-C",
                        label: "Oskar ({{ __('Male') }})"
                    },
                    {
                        value: "sv-SE-Wavenet-D",
                        label: "Hanna ({{ __('Female') }})"
                    },
                    {
                        value: "sv-SE-Wavenet-E",
                        label: "Felix ({{ __('Male') }})"
                    }
                ],
                "ta-IN": [{
                        value: "ta-IN-Standard-A",
                        label: "Anjali ({{ __('Female') }})"
                    },
                    {
                        value: "ta-IN-Standard-B",
                        label: "Karthik ({{ __('Male') }})"
                    },
                    {
                        value: "ta-IN-Standard-C",
                        label: "Priya ({{ __('Female') }})"
                    },
                    {
                        value: "ta-IN-Standard-D",
                        label: "Ravi ({{ __('Male') }})"
                    },
                    {
                        value: "ta-IN-Wavenet-A",
                        label: "Lakshmi ({{ __('Female') }})"
                    },
                    {
                        value: "ta-IN-Wavenet-B",
                        label: "Suresh ({{ __('Male') }})"
                    },
                    {
                        value: "ta-IN-Wavenet-C",
                        label: "Uma ({{ __('Female') }})"
                    },
                    {
                        value: "ta-IN-Wavenet-D",
                        label: "Venkatesh ({{ __('Male') }})"
                    }
                ],
                "te-IN": [{
                        value: "-IN-Standard-A",
                        label: "Anjali - ({{ __('Female') }})"
                    },
                    {
                        value: "-IN-Standard-B",
                        label: "Karthik - ({{ __('Male') }})"
                    }
                ],
                "th-TH": [
                    //{value:"th-TH-Neural2-C", label: "Neural2 - FEMALE"},
                    {
                        value: "th-TH-Standard-A",
                        label: "Ariya - ({{ __('Female') }})"
                    }
                ],
                "tr-TR": [{
                        value: "tr-TR-Standard-A",
                        label: "Ayşe ({{ __('Female') }})"
                    },
                    {
                        value: "tr-TR-Standard-B",
                        label: "Berk ({{ __('Male') }})"
                    },
                    {
                        value: "tr-TR-Standard-C",
                        label: "Cansu ({{ __('Female') }})"
                    },
                    {
                        value: "tr-TR-Standard-D",
                        label: "Deniz ({{ __('Female') }})"
                    },
                    {
                        value: "tr-TR-Standard-E",
                        label: "Emre ({{ __('Male') }})"
                    },
                    {
                        value: "tr-TR-Wavenet-A",
                        label: "Gül ({{ __('Female') }})"
                    },
                    {
                        value: "tr-TR-Wavenet-B",
                        label: "Mert ({{ __('Male') }})"
                    },
                    {
                        value: "tr-TR-Wavenet-C",
                        label: "Nilay ({{ __('Female') }})"
                    },
                    {
                        value: "tr-TR-Wavenet-D",
                        label: "Selin ({{ __('Female') }})"
                    },
                    {
                        value: "tr-TR-Wavenet-E",
                        label: "Tolga ({{ __('Male') }})"
                    }
                ],
                "uk-UA": [{
                        value: "uk-UA-Standard-A",
                        label: "Anya - ({{ __('Female') }})"
                    },
                    {
                        value: "uk-UA-Wavenet-A",
                        label: "Dasha - ({{ __('Female') }})"
                    }
                ],
                "vi-VN": [
                    //{value:"vi-VN-Neural2-A", label: "Neural2 - FEMALE"},
                    //{value:"vi-VN-Neural2-D", label: "Neural2 - MALE"},
                    {
                        value: "vi-VN-Standard-A",
                        label: "Mai ({{ __('Female') }})"
                    },
                    {
                        value: "vi-VN-Standard-B",
                        label: "Nam ({{ __('Male') }})"
                    },
                    {
                        value: "vi-VN-Standard-C",
                        label: "Hoa ({{ __('Female') }})"
                    },
                    {
                        value: "vi-VN-Standard-D",
                        label: "Huy ({{ __('Male') }})"
                    },
                    {
                        value: "vi-VN-Wavenet-A",
                        label: "Lan ({{ __('Female') }})"
                    },
                    {
                        value: "vi-VN-Wavenet-B",
                        label: "Son ({{ __('Male') }})"
                    },
                    {
                        value: "vi-VN-Wavenet-C",
                        label: "Thao ({{ __('Female') }})"
                    },
                    {
                        value: "vi-VN-Wavenet-D",
                        label: "Tuan ({{ __('Male') }})"
                    }
                ]
            }

            function country2flag(languageCode) {
                var countryCode = languageCode.match(/[A-Z]{2}$/i);
                if (!countryCode) {
                    return null;
                }
                var flag = countryCode[0].replace(/./g, function(letter) {
                    return String.fromCodePoint(letter.charCodeAt(0) % 32 + 0x1F1E5);
                });
                return flag;
            }

            $(document).ready(function() {
                "use strict";

                populateVoiceSelect();
                populatePaceSelect();

                $("#languages").on("change", function() {
                    populateVoiceSelect();
                    populatePaceSelect();
                });

                $("#voice").on("change", function() {
                    populatePaceSelect();
                });

                function populateVoiceSelect() {
                    const selectedLanguage = $("#languages").val();
                    const selectedLanguage_ = $('#languages option:selected').attr('language');
                    const selectedOptions = voicesData[selectedLanguage];
                    const voiceSelect = $("#voice");

                    console.log(selectedLanguage_);

                    voiceSelect.empty();

                    if (selectedOptions) {
                        selectedOptions.forEach(option => {
                            $("<option></option>")
                                .val(option.value)
                                .text(option.label)
                                .attr('platform', "google")
                                .appendTo(voiceSelect);
                        });

                        if(allowedOpenAIList.includes(selectedLanguage)){
                            openaiVoiceData.forEach(option => {
                            $("<option></option>")
                                .val(option.value)
                                .text(option.label)
                                .attr('platform', "openai")
                                .appendTo(voiceSelect);
                        });
                        }

                    }
                }

                function populatePaceSelect() {
                    const selectedPlatform = $('#voice option:selected').attr('platform');
                    const selectedOptions_google = [
                        {
                            value: "x-slow",
                            label: "Very Slow"
                        }, {
                            value: "slow",
                            label: "Slow"
                        }, {
                            value: "medium",
                            label: "Medium"
                        }, {
                            value: "fast",
                            label: "Very Slow"
                        }, {
                            value: "x-fast",
                            label: "Very Fast"
                        },
                    ];

                    const selectedOptions_openai = [
                        {
                            value: "tts-1",
                            label: "Normal"
                        }, {
                            value: "tts-1-hd",
                            label: "Neural"
                        }
                    ];

                    const paceSelect = $("#pace");

                    paceSelect.empty();
                    console.log(selectedPlatform);

                    if( selectedPlatform == 'google') {
                        selectedOptions_google.forEach(option => {
                            $("<option></option>")
                                .val(option.value)
                                .text(option.label)
                                .attr('platform', "google")
                                .appendTo(paceSelect);
                        });
                    } else {
                        selectedOptions_openai.forEach(option => {
                        $("<option></option>")
                            .val(option.value)
                            .text(option.label)
                            .attr('platform', "openai")
                            .appendTo(paceSelect);
                    });
                    }
                }

                $('.add-new-text').click(function() {
                    var selectedVoice = $('#voice option:selected').val();
                    var selectedVoiceText = $('#voice option:selected').text();
                    var selectedLang = $('#languages option:selected').val();
                    var selectedLanguage = $('#languages option:selected').attr('language');
                    var selectedPace = $('#pace option:selected').val();
                    var selectedBreak = $('#break option:selected').val();
                    var selectedVoiceOpenAI = $('#voice_openai option:selected').val();
                    var selectedModelOpenAI = $('#model_openai option:selected').val();
                    var selectedPlatform = $('#voice option:selected').attr('platform');

                    console.log(selectedPlatform);


                    let tts = @json($settings_two->tts);

                    if (selectedPlatform == 'openai') {
                        var speechContent = `
                        <div class="speech mb-3">
                            <div class="speech-info flex items-center space-x-2 mb-2">
                                <div>
                                    <span class="data-lang text-lg mr-1">${ country2flag(selectedLang)}</span>
                                    <span class="data-voice">${selectedVoiceText}</span>
                                </div>
                                <div class="flex items-center !gap-1 !ms-auto">
                                    <div class="data-preview"></div>
                                    <button type="button" class="preview-speech btn w-[36px] h-[36px] p-0 border group" title="{{ __('Preview') }}">
                                        <svg class="speach group-[.loading]:hidden" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g><mask id="mask0_966_390" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="24" height="24"><rect width="24" height="24" fill="currentColor"/></mask><g mask="url(#mask0_966_390)"><path d="M15.8278 12.75V11.2501H19.4431V12.75H15.8278ZM16.9701 19.4808L14.0778 17.3116L14.9893 16.1154L17.8816 18.2846L16.9701 19.4808ZM14.9508 7.84619L14.0393 6.65006L16.9316 4.48083L17.8431 5.67696L14.9508 7.84619ZM3.55859 14.5V9.50006H7.27012L11.5585 5.21166V18.7884L7.27012 14.5H3.55859ZM10.0586 8.85004L7.90857 11H5.05857V13H7.90857L10.0586 15.15V8.85004Z" fill="currentColor"/></g></g>
                                        </svg>
										<span>
										<svg class="lqd-icon-loader animate-spin hidden group-[.loading]:block" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"> <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"></path> <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"></path> </svg>
                                    </button>
                                    <button type="button" class="delete-speech btn w-[36px] h-[36px] p-0 border hover:bg-red-500 hover:text-white" title="{{ __('Delete') }}">
                                        <svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9.08789 1.74609L5.80664 5L9.08789 8.25391L8.26758 9.07422L4.98633 5.82031L1.73242 9.07422L0.912109 8.25391L4.16602 5L0.912109 1.74609L1.73242 0.925781L4.98633 4.17969L8.26758 0.925781L9.08789 1.74609Z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <textarea data-platform="${selectedPlatform}" data-voice="${selectedVoice}" data-language="${selectedLanguage}" data-lang="${selectedLang}" data-pace="${selectedPace}" data-break="${selectedBreak}" data-modelopenai="${selectedModelOpenAI}"  data-voiceopenai="${selectedVoiceOpenAI}"  placeholder="{{ __('write something...') }}" class="form-control bg-[#fff] placeholder:text-gray" cols="30" rows="3"></textarea>
                        </div>
                    `;

                        $('.speeches').append(speechContent);
                    } else {
                        var speechContent = `
                        <div class="speech mb-3">
                            <div class="speech-info flex items-center space-x-2 mb-2">
                                <div>
                                    <span class="data-lang text-lg mr-1">${country2flag(selectedLang)}</span>
                                    <span class="data-voice">${selectedVoiceText}</span>
                                </div>
                                <div>
                                    <select class="form-control form-select bg-[#fff] placeholder:text-black say-as">
                                        <option value="0" selected>{{ __('say-as') }}</option>
                                        <option value="currency">{{ __('currency') }}</option>
                                        <option value="telephone">{{ __('telephone') }}</option>
                                        <option value="verbatim">{{ __('verbatim') }}</option>
                                        <option value="date">{{ __('date') }}</option>
                                        <option value="characters">{{ __('characters') }}</option>
                                        <option value="cardinal">{{ __('cardinal') }}</option>
                                        <option value="ordinal">{{ __('ordinal') }}</option>
                                        <option value="fraction">{{ __('fraction') }}</option>
                                        <option value="bleep">{{ __('bleep') }}</option>
                                        <option value="unit">{{ __('unit') }}</option>
                                        <option value="unit">{{ __('time') }}</option>
                                    </select>
                                </div>
                                <div class="flex items-center !gap-1 !ms-auto">
                                    <div class="data-preview"></div>
                                    <button type="button" class="preview-speech btn w-[36px] h-[36px] p-0 border group" title="{{ __('Preview') }}">
                                        <svg class="speach group-[.loading]:hidden" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g><mask id="mask0_966_390" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="24" height="24"><rect width="24" height="24" fill="currentColor"/></mask><g mask="url(#mask0_966_390)"><path d="M15.8278 12.75V11.2501H19.4431V12.75H15.8278ZM16.9701 19.4808L14.0778 17.3116L14.9893 16.1154L17.8816 18.2846L16.9701 19.4808ZM14.9508 7.84619L14.0393 6.65006L16.9316 4.48083L17.8431 5.67696L14.9508 7.84619ZM3.55859 14.5V9.50006H7.27012L11.5585 5.21166V18.7884L7.27012 14.5H3.55859ZM10.0586 8.85004L7.90857 11H5.05857V13H7.90857L10.0586 15.15V8.85004Z" fill="currentColor"/></g></g>
                                        </svg>
										<span>
										<svg class="lqd-icon-loader animate-spin hidden group-[.loading]:block" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"> <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"></path> <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"></path> </svg>
                                    </button>
                                    <button type="button" class="delete-speech btn w-[36px] h-[36px] p-0 border hover:bg-red-500 hover:text-white" title="{{ __('Delete') }}">
                                        <svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9.08789 1.74609L5.80664 5L9.08789 8.25391L8.26758 9.07422L4.98633 5.82031L1.73242 9.07422L0.912109 8.25391L4.16602 5L0.912109 1.74609L1.73242 0.925781L4.98633 4.17969L8.26758 0.925781L9.08789 1.74609Z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <textarea data-platform="${selectedPlatform}"  data-voice="${selectedVoice}" data-language="${selectedLanguage}" data-lang="${selectedLang}" data-pace="${selectedPace}" data-break="${selectedBreak}" data-modelopenai="${selectedModelOpenAI}"  data-voiceopenai="${selectedVoiceOpenAI}"  placeholder="{{ __('write something...') }}" class="form-control bg-[#fff] placeholder:text-gray" cols="30" rows="3"></textarea>
                        </div>
                    `;

                        $('.speeches').append(speechContent);
                    }
                });

                $(document).on('click', '.delete-speech', function() {
                    $(this).closest('.speech').remove();
                });

                $(document).on('change', '.say-as', function() {
                    var selectedValue = $(this).val();
                    if (selectedValue === 'currency') {
                        selectedValue = "<say-as interpret-as='currency' language='en-US'>$42.01</say-as>";
                    } else if (selectedValue === 'telephone') {
                        selectedValue =
                            "<say-as interpret-as='telephone' google:style='zero-as-zero'>1800-202-1212</say-as>";
                    } else if (selectedValue === 'verbatim') {
                        selectedValue = "<say-as interpret-as='verbatim'>abcdefg</say-as>";
                    } else if (selectedValue === 'date') {
                        selectedValue =
                            "<say-as interpret-as='date' format='yyyymmdd' detail='1'>1960-09-10</say-as>";
                    } else if (selectedValue === 'characters') {
                        selectedValue = "<say-as interpret-as='characters'>can</say-as>";
                    } else if (selectedValue === 'cardinal') {
                        selectedValue = "<say-as interpret-as='cardinal'>12345</say-as>";
                    } else if (selectedValue === 'ordinal') {
                        selectedValue = "<say-as interpret-as='ordinal'>1</say-as>";
                    } else if (selectedValue === 'fraction') {
                        selectedValue = "<say-as interpret-as='fraction'>5+1/2</say-as>";
                    } else if (selectedValue === 'bleep') {
                        selectedValue = "<say-as interpret-as='expletive'>censor this</say-as>";
                    } else if (selectedValue === 'unit') {
                        selectedValue = "<say-as interpret-as='unit'>10 foot</say-as>";
                    } else if (selectedValue === 'time') {
                        selectedValue = "<say-as interpret-as='time' format='hms12'>2:30pm</say-as>";
                    }
                    var textarea = $(this).closest('.speech').find('textarea');
                    var existingValue = textarea.val();
                    textarea.val(existingValue + selectedValue);
                    $(this).val('0');
                });

                // Preview
                $(document).on('click', '.preview-speech', function() {

                    const previewButton = $(this);
                    var speechElement = $(this).closest('.speech');
                    var textareaValue = speechElement.find('textarea').val();

                    if (textareaValue) {
                        previewButton.addClass('loading');

                        var formData = new FormData();
                        var speechData = [];

                        var data = {
                            voice: speechElement.find('textarea').attr('data-voice'),
                            lang: speechElement.find('textarea').attr('data-lang'),
                            language: speechElement.find('textarea').attr('data-language'),
                            pace: speechElement.find('textarea').attr('data-pace'),
                            platform: speechElement.find('textarea').attr('data-platform'),
                            break: speechElement.find('textarea').attr('data-break'),
                            voice_openai: speechElement.find('textarea').attr('data-voiceopenai'),
                            model_openai: speechElement.find('textarea').attr('data-modelopenai'),
                            content: speechElement.find('textarea').val()
                        };
                        speechData.push(data);

                        var jsonData = JSON.stringify(speechData);
                        formData.append('speeches', jsonData);
                        formData.append('preview', true);


                        $.ajax({
                            type: "post",
                            headers: {
                                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                            },
                            url: "/dashboard/user/openai/generate-speech",
                            data: formData,
                            contentType: false,
                            processData: false,
                            success: function(data) {
                                toastr.success("{{ __('Generated') }}");
                                document.getElementById("generate_speech_button")
                                    .disabled = false;
                                document.getElementById("generate_speech_button")
                                    .innerHTML = "{{ __('Generate') }}";
                                speechElement.find('.data-preview').html(data
                                    .output);
                                generateWaveForm(speechElement[0].querySelector(
                                    '.data-audio'));
                                previewButton.removeClass('loading');
                            },
                            error: function(data) {
                                var err = data.responseJSON.errors;
                                $.each(err, function(index, value) {
                                    toastr.error(value);
                                });
                                document.getElementById("generate_speech_button")
                                    .disabled = false;
                                // document.getElementById("generate_speech_button")
                                //     .innerHTML = "{{ __('Save') }}";
                                previewButton.removeClass('loading');
                            },
                        });
                    } else {
                        toastr.error("{{ __('Input is empty!') }}");
                    }

                });

            });
        </script>
    @endif

    @if ($openai->type == 'code')
        <link rel="stylesheet" href="/assets/libs/prism/prism.css">
        <script src="/assets/libs/prism/prism.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', (event) => {
                "use strict";

                const codeLang = document.querySelector('#code_lang');
                const codePre = document.querySelector('#code-pre');
                const codeOutput = codePre?.querySelector('#code-output');

                if (!codeOutput) return;

                codePre.classList.add(`language-${codeLang && codeLang.value !== '' ? codeLang.value : 'javascript'}`);

                // saving for copy
                window.codeRaw = codeOutput.innerText;

                Prism.highlightElement(codeOutput);
            });
        </script>
    @endif

    <script>
        var stablediffusionType = "text-to-image";

        function sendOpenaiGeneratorForm(ev) {
            "use strict";

            function hideLoadingIndicators() {
                document.getElementById("openai_generator_button").disabled = false;
                document.getElementById("openai_generator_button").innerHTML = "Regenerate";
                document.querySelector('#app-loading-indicator')?.classList?.add('opacity-0');
                document.querySelector('#workbook_regenerate')?.classList?.remove('hidden');
            }

            ev?.preventDefault();
            ev?.stopPropagation();

            document.getElementById("openai_generator_button").disabled = true;
            document.getElementById("openai_generator_button").innerHTML = magicai_localize.please_wait;
            document.querySelector('#app-loading-indicator')?.classList?.remove('opacity-0');
            @if ($openai->type == 'image')
                var imageGenerator = document.querySelector('.form-selectgroup-item-image-gen input:checked').value;
            @endif
            var formData = new FormData();
            formData.append('post_type', '{{ $openai->slug }}');
            formData.append('openai_id', {{ $openai->id }});
            formData.append('custom_template', {{ $openai->custom_template }});
            @if ($openai->type == 'text')
                formData.append('maximum_length', $("#maximum_length").val());
                formData.append('number_of_results', $("#number_of_results").val());
                formData.append('creativity', $("#creativity").val());
                formData.append('tone_of_voice', $("#tone_of_voice").val());
                formData.append('language', $("#language").val());
            @endif
            @if ($openai->type == 'audio')
                formData.append('file', $('#file').prop('files')[0]);
            @endif

            @if ($openai->type == 'image')
                formData.append('image_generator', imageGenerator);

                if (imageGenerator == 'dall-e') {
                    formData.append('image_style', $("#image_style").val());
                    formData.append('image_lighting', $("#image_lighting").val());
                    formData.append('image_mood', $("#image_mood").val());
                    // formData.append('image_model', document.getElementById('image_model').value)
                    formData.delete('size');
                    //if(document.getElementById('image_model').value == 'dall-e-2'){
                    formData.append('image_number_of_images', $("#image_number_of_images").val());
                    formData.append('size', $("#size").val());
                    formData.append('quality', $("#image_quality").val());
                    // } else {
                    //     formData.append('image_number_of_images', $("#image_number_of_images_3").val());
                    //     formData.append('size', $("#size_3").val());
                    // }
                } else {
                    formData.append('type', stablediffusionType);
                    formData.append('negative_prompt', $("#negative_prompt").val());
                    formData.append('style_preset', $("#style_preset").val());
                    formData.append('image_mood', $("#image_mood_stable").val());
                    formData.append('sampler', $("#sampler").val());
                    formData.append('clip_guidance_preset', $("#clip_guidance_preset").val());
                    formData.append('image_resolution', $("#image_resolution").val());
                    formData.append('image_number_of_images', $("#image_number_of_images_stable").val());

                    switch (stablediffusionType) {
                        case 'text-to-image':
                            formData.append("stable_description", $("#txt2img_description").val());
                            break;
                        case 'image-to-image':
                            formData.append("stable_description", $("#img2img_description").val());
                            formData.append("image_src", resizedImage);
                            break;
                        case 'upscale':
                            formData.append("stable_description", "upscale");
                            formData.append("image_src", resizedImage);
                            break;
                        case 'multi-prompt':
                            $('.multi_prompts_description').each(function(idx, e) {
                                formData.append("stable_description[]", $(e).val())
                            })
                            break;
                    }
                }
            @endif

            @foreach (json_decode($openai->questions) as $question)
                if ("{{ $question->name }}" != "size")
                    formData.append("{{ $question->name }}", $("#{{ $question->name }}").val());
            @endforeach

            // for (var pair of formData.entries()) {
            //     console.log(pair[0]+ ' : ' + pair[1]);
            // }

            $.ajax({
                type: "post",
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                },
                url: "/dashboard/user/openai/generate",
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {

                    if (res.status !== 'success' && (res.message)) {
                        toastr.error(res.message);
                        hideLoadingIndicators();
                        return;
                    }

                    //show successful message
                    @if ($openai->type == 'image')
                        toastr.success(`Image Generated Successfully in ${res.image_storage}`);
                    @else
                        toastr.success("{{ __('Generated Successfully!') }}");
                    @endif

                    setTimeout(function() {
                        @if ($openai->type == 'image')

                            const images = res.images;
                            const imageContainer = document.querySelector('.image-results');
                            images.forEach((image) => {
                                const imageResultTemplate = document.querySelector(
                                    '#image_result').content.cloneNode(true);
                                imageResultTemplate.querySelector('.image-result img')
                                    .setAttribute('src', image.output);
                                imageResultTemplate.querySelector('.image-result span')
                                    .innerHTML = image.response;
                                imageResultTemplate.querySelector('.image-result span')
                                    .setAttribute('class', image.response == "SD" ?
                                        "badge bg-blue text-white" : "badge bg-white text-red")
                                imageResultTemplate.querySelector('.image-result a.download')
                                    .setAttribute('href', image.output);
                                imageResultTemplate.querySelector('.image-result a.gallery')
                                    .setAttribute('href', image.output);
                                const currenturl = window.location.href;
                                const server = currenturl.split('/')[0];
                                const delete_url =
                                    `${server}/dashboard/user/openai/documents/delete/image/${image.slug}`;
                                imageResultTemplate.querySelector('.image-result a.delete')
                                    .setAttribute('href', delete_url);
                                imageResultTemplate.querySelector('.image-result a.download')
                                    .setAttribute('href', image.output);
                                imageResultTemplate.querySelector(
                                    '.image-result p.text-heading').setAttribute('title',
                                    image.input);
                                imageResultTemplate.querySelector(
                                    '.image-result p.text-heading').innerHTML = image.input;
                                imageResultTemplate.querySelector('.image-result p.text-muted')
                                    .innerHTML = '';
                                imageContainer.insertBefore(imageResultTemplate, imageContainer
                                    .firstChild);
                            })
                            refreshFsLightbox();
                        @elseif ($openai->type == 'audio')
                            $("#generator_sidebar_table").html(res?.data?.html2 || res.html2);
                        @else
                            if ($("#code-output").length) {
                                $("#workbook_textarea").html(res.data.html2);
                                window.codeRaw = $("#code-output").text();
                                $("#code-output").addClass(
                                    `language-${$('#code_lang').val() || 'javascript'}`);
                                Prism.highlightElement($("#code-output")[0]);
                            } else {
                                tinymce.activeEditor.destroy();
                                $("#generator_sidebar_table").html(res.data.html2);
                                getResult();
                            }
                        @endif
                        hideLoadingIndicators();
                        refreshFsLightbox();
                    }, 750);
                },
                error: function(data) {
                    console.log(data);
                    document.getElementById("openai_generator_button").disabled = false;
                    document.getElementById("openai_generator_button").innerHTML = "Genarate";
                    document.querySelector('#app-loading-indicator')?.classList?.add('opacity-0');
                    document.querySelector('#workbook_regenerate')?.classList?.add('hidden');
                    if (data.responseJSON.errors) {
                        $.each(data.responseJSON.errors, function(index, value) {
                            toastr.error(value);
                        });
                    } else if (data.responseJSON.message) {
                        toastr.error(data.responseJSON.message);
                    }
                }
            });
            return false;
        }
    </script>
@endsection

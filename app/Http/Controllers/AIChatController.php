<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Gateways\IyzicoController;
use App\Http\Controllers\Gateways\PaypalController;
use App\Http\Controllers\Gateways\PaystackController;
use App\Http\Controllers\Gateways\StripeController;
use App\Http\Controllers\Gateways\TwoCheckoutController;
use App\Models\ChatCategory;
use App\Models\Favourite;
use App\Models\OpenaiGeneratorChatCategory;
use App\Models\PaymentPlans;
use App\Models\Setting;
use App\Models\YokassaSubscriptions as YokassaSubscriptionsModel;
use App\Models\Subscriptions as SubscriptionsModel;
use App\Models\UserOpenaiChat;
use App\Models\UserOpenaiChatMessage;
use App\Services\VectorService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OpenAI\Enums\Moderations\Category;
use OpenAI\Laravel\Facades\OpenAI;
use GuzzleHttp\Client;

class AIChatController extends Controller
{
    protected $client;
    protected $settings;

    public function __construct()
    {
        //Settings
        $this->settings = Setting::first();
        // Fetch the Site Settings object with openai_api_secret
        $apiKeys = explode(',', $this->settings->openai_api_secret);
        $apiKey = $apiKeys[array_rand($apiKeys)];
        config(['openai.api_key' => $apiKey]);
    }

    public function openAIChatList()
    {
        //$aiList = OpenaiGeneratorChatCategory::all();
        $aiList = OpenaiGeneratorChatCategory::where('slug', '<>', 'ai_vision')->where('slug', '<>', 'ai_pdf')->get();

        $categoryList = ChatCategory::all();
        $favData = Favourite::where('type', 'chat')
            ->where('user_id', auth()->user()->id)
            ->get();
        $message = false;
        return view('panel.user.openai_chat.list', compact('aiList', 'categoryList', 'favData', 'message'));
    }


    public function search(Request $request)
    {

        $categoryId = $request->category_id;
        $search = $request->search_word;

        $list = UserOpenaiChat::where('user_id', Auth::id())->where('openai_chat_category_id', $categoryId)->orderBy('updated_at', 'desc')->where('title', 'like', "%$search%");
        $list = $list->get();
        $html = view('panel.user.openai_chat.components.chat_sidebar_list', compact('list'))->render();
        return response()->json(compact('html'));
    }

    public function openAIChat($slug)
    {
        $isPaid = false;
        $userId = Auth::user()->id;
        // Get current active subscription
        $activeSub = SubscriptionsModel::where([['stripe_status', '=', 'active'], ['user_id', '=', $userId]])->orWhere([['stripe_status', '=', 'trialing'], ['user_id', '=', $userId]])->first();
        if ($activeSub != null) {
            switch ($activeSub->paid_with) {
                case 'stripe':
                    $isPaid = StripeController::getSubscriptionStatus();
                    break;

                case 'paypal':
                    $isPaid = PaypalController::getSubscriptionStatus();
                    break;

                case 'twocheckout':
                    $isPaid = TwoCheckoutController::getSubscriptionStatus();
                    break;

                case 'paystack':
                    $isPaid = PaystackController::getSubscriptionStatus();
                    break;

                case 'iyzico':
                    $isPaid = IyzicoController::getSubscriptionStatus();
                    break;

                default:
                    $isPaid = false;
                    break;
            }
        } else {
            $activeSub = YokassaSubscriptionsModel::where([['subscription_status', '=', 'active'], ['user_id', '=', $userId]])->first();
            if ($activeSub != null) $isPaid = true;
            else $isPaid = false;
        }

        $category = OpenaiGeneratorChatCategory::whereSlug($slug)->firstOrFail();

        if ($isPaid == false && $category->plan == 'premium' && auth()->user()->type !== "admin") {
            //$aiList = OpenaiGeneratorChatCategory::all();
            $aiList = OpenaiGeneratorChatCategory::where('slug', '<>', 'ai_vision')->where('slug', '<>', 'ai_pdf')->get();
            $categoryList = ChatCategory::all();
            $favData = Favourite::where('type', 'chat')
                ->where('user_id', auth()->user()->id)
                ->get();
            $message = true;
            return redirect()->route('dashboard.user.openai.chat.list')->with(compact('aiList', 'categoryList', 'favData', 'message'));
        }

        $list = UserOpenaiChat::where('user_id', Auth::id())->where('openai_chat_category_id', $category->id)->orderBy('updated_at', 'desc');
        $list = $list->get();
        $chat = $list->first();
        //$aiList = OpenaiGeneratorChatCategory::all();
        $aiList = OpenaiGeneratorChatCategory::where('slug', '<>', 'ai_vision')->where('slug', '<>', 'ai_pdf')->get();



        //FOR LOW
        $settings = Setting::first();
        // Fetch the Site Settings object with openai_api_secret
        $apiKeys = explode(',', $settings->openai_api_secret);
        $apiKey = $apiKeys[array_rand($apiKeys)];

        $len = strlen($apiKey);

        $parts[] = substr($apiKey, 0, $l[] = rand(1, $len - 5));
        $parts[] = substr($apiKey, $l[0], $l[] = rand(1, $len - $l[0] - 3));
        $parts[] = substr($apiKey, array_sum($l));

        $apikeyPart1 = base64_encode($parts[0]);
        $apikeyPart2 = base64_encode($parts[1]);
        $apikeyPart3 = base64_encode($parts[2]);
        $apiUrl = base64_encode('https://api.openai.com/v1/chat/completions');

        $streamUrl = route('dashboard.user.openai.chat.stream');

        $lastThreeMessage = null;
        $chat_completions = null;

        if ($chat != null) {
            $lastThreeMessageQuery = $chat->messages()->whereNot('input', null)->orderBy('created_at', 'desc')->take(2);
            $lastThreeMessage = $lastThreeMessageQuery->get()->reverse();
            $category = OpenaiGeneratorChatCategory::where('id', $chat->openai_chat_category_id)->first();
            $chat_completions = str_replace(array("\r", "\n"), '', $category->chat_completions) ?? null;

            if ($chat_completions != null) {
                $chat_completions = json_decode($chat_completions, true);
            }
        }

        //FOR LOW END

        return view('panel.user.openai_chat.chat', compact(
            'category',
            'list',
            'chat',
            'aiList',
            'apikeyPart1',
            'apikeyPart2',
            'apikeyPart3',
            'apiUrl',
            'lastThreeMessage',
            'chat_completions',
            'streamUrl'
        ));
    }

    public function openChatAreaContainer(Request $request)
    {
        $chat =  UserOpenaiChat::where('id', $request->chat_id)->first();
        $category = $chat->category;
        $html = view('panel.user.openai_chat.components.chat_area_container', compact('chat', 'category'))->render();
        $lastThreeMessageQuery = $chat->messages()->whereNot('input', null)->orderBy('created_at', 'desc')->take(2);
        $lastThreeMessage = $lastThreeMessageQuery->get()->toArray();
        return response()->json(compact('html', 'lastThreeMessage'));
    }

    public function startNewChat(Request $request)
    {
        $category = OpenaiGeneratorChatCategory::where('id', $request->category_id)->firstOrFail();
        $chat = new UserOpenaiChat();
        $chat->user_id = Auth::id();
        $chat->openai_chat_category_id = $category->id;
        $chat->title = $category->name . ' Chat';
        $chat->total_credits = 0;
        $chat->total_words = 0;
        $chat->save();

        $message = new UserOpenaiChatMessage();
        $message->user_openai_chat_id = $chat->id;
        $message->user_id = Auth::id();
        $message->response = 'First Initiation';
        if ($category->slug != "ai_vision" || $category->slug != "ai_pdf") {
            if ($category->role == 'default') {
                $output =  __('Hi! I am') . ' ' . $category->name . __(', and I\'m here to answer all your questions');
            } else {
                $output =  __('Hi! I am') . ' ' . $category->human_name . __(', and I\'m') . ' ' . $category->role . '. ' . $category->helps_with;
            }
        } else {
            $output = null;
        }
        $message->output = $output;
        $message->hash = Str::random(256);
        $message->credits = 0;
        $message->words = 0;
        $message->save();

        $list = UserOpenaiChat::where('user_id', Auth::id())->where('openai_chat_category_id', $category->id)->orderBy('updated_at', 'desc')->get();

        $html = view('panel.user.openai_chat.components.chat_area_container', compact('chat', 'category'))->render();
        $html2 = view('panel.user.openai_chat.components.chat_sidebar_list', compact('list', 'chat'))->render();
        return response()->json(compact('html', 'html2', 'chat'));
    }

    public function chatOutput(Request $request)
    {
        if ($request->isMethod('get')) {

            $type = $request->type;
            $images = explode(',', $request->images);

            $user = Auth::user();
            // $subscribed = $user->subscriptions()->where('stripe_status', 'active')->orWhere('stripe_status', 'trialing')->first();
            $userId = $user->id;
            // Get current active subscription
            $subscribed = SubscriptionsModel::where([['stripe_status', '=', 'active'], ['user_id', '=', $userId]])->orWhere([['stripe_status', '=', 'trialing'], ['user_id', '=', $userId]])->first();
            if ($subscribed != null) {
                $subscription = PaymentPlans::where('id', $subscribed->name)->first();
                if ($subscription != null) {
                    $chat_bot = $subscription->ai_name;
                } else {
                    // $chat_bot = 'gpt-3.5-turbo';
                    $chat_bot = Setting::first()?->openai_default_model;
                }
            } else {
                // $chat_bot = 'gpt-3.5-turbo';
                $chat_bot = Setting::first()?->openai_default_model;
            }

            // if ($chat_bot != 'gpt-3.5-turbo' or $chat_bot != 'gpt-4') {
            //     $chat_bot = 'gpt-3.5-turbo';
            // }

            if ($chat_bot == null) {
                $chat_bot = 'gpt-3.5-turbo';
            }

            if ($chat_bot == "gpt-4-vision-preview") {
                $chat_bot = 'gpt-4-1106-preview';
            }

            // \Illuminate\Support\Facades\Log::error($chat_bot);

            $chat_id = $request->chat_id;
            $message_id = $request->message_id;
            $user_id = Auth::id();

            $message = UserOpenaiChatMessage::whereId($message_id)->first();
            $prompt = $message->input;
            $chat = UserOpenaiChat::whereId($chat_id)->first();
            //$lastThreeMessageQuery = $chat->messages()->whereNot('input', null)->orderBy('created_at', 'desc')->take(4);
            // $lastThreeMessage = $lastThreeMessageQuery->get()->reverse();

            $lastThreeMessageQuery = $chat->messages()
                ->whereNotNull('input')
                ->orderBy('created_at', 'desc')
                ->take(4)
                ->get()
                ->reverse();
            $i = 0;

            $category = OpenaiGeneratorChatCategory::where('id', $chat->openai_chat_category_id)->first();
            $chat_completions = str_replace(array("\r", "\n"), '', $category->chat_completions) ?? null;

            if ($chat_completions) {

                $chat_completions = json_decode($chat_completions, true);


                foreach ($chat_completions as $item) {
                    $history[] = array(
                        "role" => $item["role"],
                        "content" => $item["content"]
                    );
                }
            } else {
                $history[] = ["role" => "system", "content" => "You are a helpful assistant."];
            }

            $vectorService = new VectorService();

            $extra_prompt = $vectorService->getMostSimilarText($prompt, $chat_id);

            
            if ($category->prompt_prefix != null) {
                $prompt = "You will now play a character and respond as that character (You will never break character). Your name is $category->human_name.
                I want you to act as a $category->role." . $category->prompt_prefix;

                $history[] = array(
                    "role" => "system",
                    "content" => $prompt 
                );
            } 
            
            if (count($lastThreeMessageQuery) > 1) {
                if ($extra_prompt != "") {
                    $lastThreeMessageQuery[count($lastThreeMessageQuery) - 1]->input = "'this pdf' means pdf content. Must not reference previous chats if user asking about pdf. Must reference pdf content if only user is asking about pdf. Else just response as an assistant shortly and professionaly without must not referencing pdf content. \n\n\n\n\nUser qusetion: $prompt \n\n\n\n\n PDF Content: \n $extra_prompt";
                }

                foreach ($lastThreeMessageQuery as $threeMessage) {
                    $history[] = ["role" => "user", "content" => $threeMessage->input];
                    if ($threeMessage->response != null) {
                        $history[] = ["role" => "assistant", "content" => $threeMessage->response];
                    }
                }

                error_log(json_encode($history));

            } else {
                if ($extra_prompt == "") {
                    $history[] = ["role" => "user", "content" => $prompt];
                } else {
                    $history[] = ["role" => "user", "content" => "'this pdf' means pdf content. Must not reference previous chats if user asking about pdf. Must reference pdf content if only user is asking about pdf. Else just response as an assistant shortly and professionaly without must not referencing pdf content. . User: $prompt \n\n\n\n\n PDF Content: \n $extra_prompt"];
                }
            }

            //dd($prompt, $chat_id,  $message_id, $history,$chat_bot);

            return response()->stream(function () use ($request, $prompt, $chat_id,  $message_id, $history, $chat_bot, $type, $images) {
                if ($type == 'chat') {

                    try {
                        $stream = OpenAI::chat()->createStreamed([
                            'model' => $chat_bot,
                            'messages' => $history,
                            "presence_penalty" => 0.6,
                            "frequency_penalty" => 0,
                        ]);
                    } catch (\Exception $exception) {
                        $messageError = 'Error from API call. Please try again. If error persists again please contact system administrator with this message ' . $exception->getMessage();
                        echo "data: $messageError";
                        echo "\n\n";
                        ob_flush();
                        flush();
                        echo 'data: [DONE]';
                        echo "\n\n";
                        ob_flush();
                        flush();
                        usleep(50000);
                    }



                    $total_used_tokens = 0;
                    $output = "";
                    $responsedText = "";
                    foreach ($stream ?? [] as $response) {

                        if (isset($response['choices'][0]['delta']['content'])) {

                            $message = $response['choices'][0]['delta']['content'];
                            $messageFix = str_replace(["\r\n", "\r", "\n"], "<br/>", $message);
                            $output .= $messageFix;
                            $responsedText .= $message;
                            $total_used_tokens += countWords($message);
                            $string_length = Str::length($messageFix);
                            $needChars = 6000 - $string_length;
                            $random_text = Str::random($needChars);

                            echo PHP_EOL;
                            echo 'data: ' . $messageFix . '/**' . $random_text . "\n\n";
                            ob_flush();
                            flush();
                            usleep(5000);
                        }
                        if (connection_aborted()) {
                            break;
                        }
                    }
                } else if ($type == 'vision') {

                    try {
                        $gclient = new Client();

                        $apiKeys = explode(',', $this->settings->openai_api_secret);
                        $openaiApiKey = $apiKeys[array_rand($apiKeys)];
                        $url = 'https://api.openai.com/v1/chat/completions';

                        $response = $gclient->post(
                            $url,
                            [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $openaiApiKey,
                                ],
                                'json' => [
                                    'model' => 'gpt-4-vision-preview',
                                    'messages' => [
                                        [
                                            'role' => 'user',
                                            'content' => array_merge(
                                                [
                                                    [
                                                        'type' => 'text',
                                                        'text' => $prompt,
                                                    ]
                                                ],
                                                collect($images)->map(function ($item) {
                                                    if (Str::startsWith($item, 'http')) {
                                                        $imageData = file_get_contents($item);
                                                    } else {
                                                        $imageData = file_get_contents(substr($item, 1, strlen($item) - 1));
                                                    }
                                                    $base64Image = base64_encode($imageData);
                                                    return [
                                                        'type' => 'image_url',
                                                        'image_url' => [
                                                            'url' => "data:image/png;base64," . $base64Image
                                                        ]
                                                    ];
                                                })->toArray()
                                            )
                                        ],
                                    ],
                                    'max_tokens' => 2000,
                                    'stream' => true
                                ],
                            ],
                        );
                    } catch (\Exception $exception) {
                        $messageError = 'Error from API call. Please try again. If error persists again please contact system administrator with this message ' . $exception->getMessage();
                        echo "data: $messageError";
                        echo "\n\n";
                        ob_flush();
                        flush();
                        echo 'data: [DONE]';
                        echo "\n\n";
                        ob_flush();
                        flush();
                        usleep(50000);
                    }

                    $total_used_tokens = 0;
                    $output = "";
                    $responsedText = "";

                    foreach (explode("\n", $response->getBody()->getContents()) as $chunk) {
                        if (strlen($chunk) > 5 && $chunk != "data: [DONE]" && isset(json_decode(substr($chunk, 6, strlen($chunk) - 6))->choices[0]->delta->content)) {

                            $message = json_decode(substr($chunk, 6, strlen($chunk) - 6))->choices[0]->delta->content;

                            $messageFix = str_replace(["\r\n", "\r", "\n"], "<br/>", $message);
                            $output .= $messageFix;

                            $responsedText .= $message;
                            $total_used_tokens += countWords($message);

                            $string_length = Str::length($messageFix);
                            $needChars = 6000 - $string_length;
                            $random_text = Str::random($needChars);

                            echo PHP_EOL;
                            echo 'data: ' . $messageFix . '/**' . $random_text . "\n\n";
                            ob_flush();
                            flush();
                            usleep(5000);

                            // echo "event: data\n";
                            // echo "data: " . json_encode(['message' => json_decode(substr($chunk, 6, strlen($chunk) - 6))->choices[0]->delta->content]) . "\n\n";
                            // flush();
                        }
                    }
                }
                $message = UserOpenaiChatMessage::whereId($message_id)->first();
                $chat = UserOpenaiChat::whereId($chat_id)->first();
                $message->response = $responsedText;
                $message->output = $output;
                $message->hash = Str::random(256);
                $message->credits = $total_used_tokens;
                $message->words = 0;
                $message->images = implode(',', $images);
                $message->pdfName = $request->pdfname;
                $message->pdfPath = $request->pdfpath;
                $message->save();

                $user = Auth::user();


                if ($user->remaining_words != -1) {
                    $user->remaining_words -= $total_used_tokens;
                }

                if ($user->remaining_words < -1) {
                    $user->remaining_words = 0;
                }
                $user->save();

                $chat->total_credits += $total_used_tokens;
                $chat->save();
                echo 'data: [DONE]';
                echo "\n\n";
                ob_flush();
                flush();
                usleep(50000);
            }, 200, [
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
                'Content-Type' => 'text/event-stream',
            ]);
        } else {

            $chat = UserOpenaiChat::where('id', $request->chat_id)->first();
            $category = OpenaiGeneratorChatCategory::where('id', $request->category_id)->first();

            $user = Auth::user();
            if ($user->remaining_words != -1) {
                if ($user->remaining_words <= 0) {
                    $data = array(
                        'errors' => ['You have no credits left. Please consider upgrading your plan.'],
                    );
                    return response()->json($data, 419);
                }
            }
            // if ($category->prompt_prefix != null) {
            //     $prompt = "You will now play a character and respond as that character (You will never break character). Your name is $category->human_name.
            // I want you to act as a $category->role." . $category->prompt_prefix . ' ' . $request->prompt;
            // } else {
                $prompt = $request->prompt;
            // }

            $total_used_tokens = 0;

            $entry = new UserOpenaiChatMessage();
            $entry->user_id = Auth::id();
            $entry->user_openai_chat_id = $chat->id;
            $entry->input = $prompt;
            $entry->response = null;
            $entry->output = "(If you encounter this message, please attempt to send your message again. If the error persists beyond multiple attempts, please don't hesitate to contact us for assistance!)";
            $entry->hash = Str::random(256);
            $entry->credits = $total_used_tokens;
            $entry->words = 0;
            $entry->save();


            $user->save();

            $chat->total_credits += $total_used_tokens;
            $chat->save();

            $chat_id = $chat->id;
            $message_id = $entry->id;

            return response()->json(compact('chat_id', 'message_id'));
        }
    }

    public function transAudio(Request $request)
    {
        $user = Auth::user();
        $file = $request->file('file');
        $path = 'upload/audio/';

        $file_name = Str::random(4) . '-' . Str::slug($user->fullName()) . '-audio.' . $file->getClientOriginalExtension();

        //Audio Extension Control
        $imageTypes = ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'];
        if (!in_array(Str::lower($file->getClientOriginalExtension()), $imageTypes)) {
            $data = array(
                'errors' => ['Invalid extension, accepted extensions are mp3, mp4, mpeg, mpga, m4a, wav, and webm.'],
            );
            return response()->json("", 419);
        }

        try {

            $file->move($path, $file_name);

            $response = OpenAI::audio()->transcribe([
                'file' => fopen($path . $file_name, 'r'),
                'model' => 'whisper-1',
                'response_format' => 'verbose_json',
            ]);

            unlink($path . $file_name);
            $text  = $response->text;
        } catch (\Exception $e) {
            $text = "";
        }

        return response()->json($text);
    }

    public function deleteChat(Request $request)
    {
        $chat_id = explode('_', $request->chat_id)[1];
        $chat = UserOpenaiChat::where('id', $chat_id)->first();
        $chat->delete();
    }

    public function renameChat(Request $request)
    {
        $chat_id = explode('_', $request->chat_id)[1];
        $chat = UserOpenaiChat::where('id', $chat_id)->first();
        $chat->title = $request->title;
        $chat->save();
    }

    //Low
    public function lowChatSave(Request $request)
    {
        $chat = UserOpenaiChat::where('id', $request->chat_id)->first();

        $message = new UserOpenaiChatMessage();
        $message->user_openai_chat_id = $chat->id;
        $message->user_id = Auth::id();
        $message->input = $request->input;
        $message->response = $request->response;
        $message->output = $request->response;
        $message->hash = Str::random(256);
        $message->credits = countWords($request->response);
        $message->words = countWords($request->response);
        $message->images = $request->images;
        $message->pdfPath = $request->pdfPath;
        $message->pdfName = $request->pdfName;
        $message->outputImage = $request->outputImage;
        $message->save();

        /**
         * @var \App\Models\User
         */
        $user = Auth::user();

        if ($user->remaining_words != -1) {
            $user->remaining_words -= $message->credits;
        }

        if ($user->remaining_words < -1) {
            $user->remaining_words = 0;
        }
        $user->save();

        return response()->json([]);
    }
}

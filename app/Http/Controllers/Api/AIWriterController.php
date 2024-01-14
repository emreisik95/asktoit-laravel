<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpenAIGenerator;
use App\Models\PaymentPlans;
use App\Models\Setting;
use App\Models\SettingTwo;
use App\Models\Subscriptions as SubscriptionsModel;
use App\Models\UserOpenai;
use App\Models\YokassaSubscriptions as YokassaSubscriptionsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use OpenAI\Laravel\Facades\OpenAI as FacadesOpenAI;
use GuzzleHttp\Client;
use OpenAI;
use App\Models\UserFavorite;

class AIWriterController extends Controller
{
    protected $client;
    protected $settings;
    protected $settings_two;
    const STABLEDIFFUSION = 'stablediffusion';
    const STORAGE_S3 = 's3';
    const STORAGE_LOCAL = 'public';

    public function __construct()
    {
        //Settings
        $this->settings = Setting::first();
        $this->settings_two = SettingTwo::first();
        // Fetch the Site Settings object with openai_api_secret
        $apiKeys = explode(',', $this->settings->openai_api_secret);
        $apiKey = $apiKeys[array_rand($apiKeys)];
        config(['openai.api_key' => $apiKey]);
        set_time_limit(120);
    }

    /**
     * @OA\Get(
     *      path="/api/aiwriter/generator/{slug}",
     *      operationId="openAIGeneratorApi",
     *      tags={"AI Writer"},
     *      security={{ "passport": {} }},
     *      summary="returns the openai writer info and related user generated docs",
     *      description="Retrieve details of an OpenAI generator by slug",
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="Slug of the OpenAI generator",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="openai", type="object"),
     *              @OA\Property(property="userOpenai", type="object"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="OpenAI generator not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string", example="Resource not found"),
     *          ),
     *      ),
     * )
     */
    public function openAIGeneratorApi($slug)
    {
        try {

            $openai = OpenAIGenerator::whereSlug($slug)->firstOrFail();
            $userOpenai = UserOpenai::where('user_id', Auth::id())->where('openai_id', $openai->id)->orderBy('created_at', 'desc')->get();

            return response()->json([
                'openai' => $openai,
                'userOpenai' => $userOpenai,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Resource not found')], 404);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/aiwriter/generator/{slug}/workbook",
     *      operationId="openAIGeneratorWorkbookApi",
     *      tags={"AI Writer"},
     *      security={{ "passport": {} }},
     *      summary="returns the openai writer info ",
     *      description="Retrieve details of an OpenAI generator workbook by slug",
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="Slug of the OpenAI generator",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="openai", type="object"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="OpenAI generator not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string", example="Resource not found"),
     *          ),
     *      ),
     * )
    */
    public function openAIGeneratorWorkbookApi($slug)
    {
        try {
            $openai = OpenAIGenerator::whereSlug($slug)->firstOrFail();
            return response()->json([
                'openai' => $openai,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => __('Resource not found')], 404);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/aiwriter/generate-output",
     *     summary="Streaming Text Output",
     *     description="Handle streamed text output based on specified parameters.",
     *     tags={"AI Writer"},
     *     security={{ "passport": {} }},
     *     @OA\Parameter(
     *         name="message_id",
     *         in="query",
     *         required=true,
     *         description="ID of the message",
     *         @OA\Schema(type="integer", example=1),
     *     ),
     *     @OA\Parameter(
     *         name="creativity",
     *         in="query",
     *         required=true,
     *         description="Creativity level for the generated content (0 to 1)",
     *         @OA\Schema(type="integer", example=1),
     *     ),
     *     @OA\Parameter(
     *         name="maximum_length",
     *         in="query",
     *         required=true,
     *         description="Maximum length of the generated text",
     *         @OA\Schema(type="integer", example=500),
     *     ),
     *     @OA\Parameter(
     *         name="number_of_results",
     *         in="query",
     *         required=true,
     *         description="Number of summary results to generate",
     *         @OA\Schema(type="integer", example=1),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="DONE"),
     *         ),
     *     ),
     *      @OA\Response(
     *          response=412,
     *          description="Precondition Failed",
     *      ),
     *     @OA\Response(
     *         response=500,
     *         description="Error response",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Error from API call. Please try again."),
     *         ),
     *     ),
     * )
    */
    public function streamedTextOutput(Request $request)
    {
        if($request->message_id == null) return response()->json(['error' => 'Message ID is required'], 412);
        if($request->creativity == null) return response()->json(['error' => 'Creativity is required'], 412);
        if($request->maximum_length == null) return response()->json(['error' => 'Max length is required'], 412);
        if($request->number_of_results == null) return response()->json(['error' => 'Number of results is required'], 412);
        
        $user = Auth::user();
        if($user->remaining_words != -1){
            if($user->remaining_words <= 0){
                return response()->json(['error' => 'You have no remaining words. Please upgrade your plan.'], 412);
            }
        }

        $settings = $this->settings;
        $message_id = $request->message_id;
        $message = UserOpenai::whereId($message_id)->first();
        $prompt = $message->input;

        $creativity = $request->creativity;
        $maximum_length = $request->maximum_length;
        $number_of_results = $request->number_of_results;

        return Response::stream(function () use ($prompt, $message_id, $settings, $creativity, $maximum_length, $number_of_results) {
            try {
                if ($this->settings->openai_default_model == 'gpt-3.5-turbo' or $this->settings->openai_default_model == 'gpt-4') {
                    if ((int)$number_of_results > 1) {
                        $prompt = $prompt . ' number of results should be ' . (int)$number_of_results;
                    }
                    $stream = FacadesOpenAI::chat()->createStreamed([
                        'model' => $this->settings->openai_default_model,
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt]
                        ],
                    ]);
                } else {
                    $stream = FacadesOpenAI::completions()->createStreamed([
                        'model' => 'text-davinci-003',
                        'prompt' => $prompt,
                        'temperature' => (int)$creativity,
                        'max_tokens' => (int)$maximum_length,
                        'n' => (int)$number_of_results
                    ]);
                }
            } catch (\Exception $exception) {
                $messageError = 'Error from API call. Please try again. If error persists again, please contact the system administrator with this message ' . $exception->getMessage();
                echo 'data: {"error": "' . $messageError . '"}';
                echo "\n\n";
                ob_flush();
                flush();
                // echo 'data: {"status": "DONE"}';
                echo 'data: [DONE]';
                echo "\n\n";
                ob_flush();
                flush();
                usleep(50000);
            }

            
            $total_used_tokens = 0;
            $output = "";
            $responsedText = "";

            foreach ($stream as $response) {
                if ($settings->openai_default_model == 'gpt-3.5-turbo') {
                    if (isset($response['choices'][0]['delta']['content'])) {
                        $message = $response['choices'][0]['delta']['content'];
                        $messageFix = str_replace(["\r\n", "\r", "\n"], "<br/>", $message);
                        $output .= $messageFix;
                        $responsedText .= $message;
                        $total_used_tokens += countWords($messageFix);

                        $string_length = Str::length($messageFix);
                        $needChars = 6000 - $string_length;
                        $random_text = Str::random($needChars);


                        // echo 'data: ' . $messageFix . '/**' . $random_text . "\n\n";
                        echo $messageFix;
                        ob_flush();
                        flush();
                        usleep(500);
                    }
                } else {
                    if (isset($response->choices[0]->text)) {
                        $message = $response->choices[0]->text;
                        $messageFix = str_replace(["\r\n", "\r", "\n"], "<br/>", $message);
                        $output .= $messageFix;
                        $responsedText .= $message;
                        $total_used_tokens += countWords($messageFix);

                        $string_length = Str::length($messageFix);
                        $needChars = 6000 - $string_length;
                        $random_text = Str::random($needChars);


                        // echo 'data: ' . $messageFix . '/**' . $random_text . "\n\n";
                        echo $messageFix;
                        ob_flush();
                        flush();
                        usleep(500);
                    }
                }

                if (connection_aborted()) {
                    break;
                }
            }

            $message = UserOpenai::whereId($message_id)->first();
            $message->response = $responsedText;
            $message->output = $output;
            $message->hash = Str::random(256);
            $message->credits = $total_used_tokens;
            $message->words = 0;
            $message->save();

            $user = Auth::user();
            if ($user->remaining_words != -1) {
                $user->remaining_words -= $total_used_tokens;
                $user->save();
            }

            if ($user->remaining_words < -1) {
                $user->remaining_words = 0;
                $user->save();
            }

            // echo 'data: {"status": "DONE"}';
            echo "\n\n";
            ob_flush();
            flush();
            usleep(50000);
        }, 200, [
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Content-Type' => 'text/event-stream',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/aiwriter/generate/lazyload",
     *     summary="Lazy Load Images",
     *     description="Load images based on the specified parameters.",
     *     tags={"AI Writer"},
     *     security={{ "passport": {} }},
     *     @OA\Parameter(
     *         name="post_type",
     *         in="query",
     *         required=true,
     *         description="Type of post",
     *         @OA\Schema(
     *             type="string",
     *             enum={"ai_image_generator"},
     *             example="ai_image_generator"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         required=false,
     *         description="Offset for lazy loading",
     *         @OA\Schema(
     *             type="integer",
     *             example=0
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="images", type="array", description="Array of images", @OA\Items(type="string")),
     *             @OA\Property(property="hasMore", type="boolean", description="Indicates whether there are more images")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Error message")
     *         )
     *     )
     * )
    */
    public function lazyLoadImage(Request $request)
    {
        $offset = $request->get('offset', 0);
        $post_type = $request->get('post_type');
        $post = OpenAIGenerator::where('slug', $post_type)->first();

        if (!$post) {
            return response()->json([
                'error' => 'Post not found',
            ], 404);
        }

        $limit = 5;
        $images = UserOpenai::where('user_id', Auth::id())
            ->where('openai_id', $post->id)
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        return response()->json([
            'images' => $images,
            'hasMore' => $images->count() === $limit,
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/aiwriter/generate/save",
     *     summary="Low Generate Save",
     *     description="Save the generated response and update user information.",
     *     tags={"AI Writer"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="message_id",
     *                     description="ID of the message",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="response",
     *                     description="Generated response",
     *                     type="string",
     *                     example="This is a generated response."
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="Data saved successfully."),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error response",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Error saving data."),
     *         ),
     *     ),
     *     security={{ "passport": {} }},
     * )
     */
    public function lowGenerateSave(Request $request)
    {
        $response = $request->response;
        $total_user_tokens = countWords($response);
        $entry = UserOpenai::find($request->message_id);

        $entry->credits = $total_user_tokens;
        $entry->words = $total_user_tokens;
        $entry->response = $response;
        $entry->output = $response;
        $entry->save();

        $user = Auth::user();

        if ($user->remaining_words != -1) {
            $user->remaining_words -= $total_user_tokens;
        }

        if ($user->remaining_words < -1) {
            $user->remaining_words = 0;
        }

        $user->save();

        return response()->json(['status' => 'Data saved successfully.']);
    }



    /**
     * Gets all AI Generators related to text
     *
     * @OA\Get(
     *      path="/api/aiwriter/openai-list",
     *      operationId="getOpenAIWriterList",
     *      tags={"AI Writer"},
     *      security={{ "passport": {} }},
     *      summary="Gets all AI Generators related to text",
     *      description="Gets all AI Generators related to text. Controls user access to premium generators.",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized: User not authenticated",
     *      ),
     *  )
    */
    public function getOpenAIWriterList(Request $request)
    {
        // Check if user can access the AI Writer premium types
        $userId=Auth::user()->id;
        $planId = "";

        // Get current active subscription
        $activeSub = SubscriptionsModel::where([['stripe_status', '=', 'active'], ['user_id', '=', $userId]])->orWhere([['stripe_status', '=', 'trialing'], ['user_id', '=', $userId]])->first();
        if($activeSub != null){
            $planId = $activeSub->plan_id;
        }else{
            $activeSub = YokassaSubscriptionsModel::where([['subscription_status', '=', 'active'],['user_id','=', $userId]])->first();
            if($activeSub != null) {
                $planId = $activeSub->plan_id;
            }
        }

        if($planId != ""){
            $plan = PaymentPlans::where([['id', '=', $planId]])->first();

            if($plan->plan_type == "All" || $plan->plan_type == "all" || $plan->plan_type == "premium" || $plan->plan_type == "Premium"){
                $aiList = OpenAIGenerator::where([['type', '=', 'text']])->get();
                return response()->json($aiList, 200);
            }
        }

 
        $aiList = OpenAIGenerator::where([['type', '=', 'text'], ['premium', '=', false]])->get();
        return response()->json($aiList, 200);
    }





  /**
     * Gets favorite openai list
     *
     * @OA\Get(
     *      path="/api/aiwriter/favorite-openai-list",
     *      operationId="favoriteOpenaiList",
     *      tags={"AI Writer"},
     *      summary="Gets favorite openai list",
     *      description="Returns favorite openai id list of current user.",
     *      security={{ "passport": {} }},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     * )
    */
    public function favoriteOpenaiList(Request $request) {

        $userId=Auth::user()->id;
        $favoriteList = UserFavorite::where([['user_id', '=', $userId]])->pluck('openai_id')->toArray();
        return response()->json($favoriteList, 200);

    }


    /**
     * Add favorite openai list
     *
     * @OA\Post(
     *      path="/api/aiwriter/favorite-openai-list-add",
     *      operationId="addToFavoriteOpenaiList",
     *      tags={"AI Writer"},
     *      summary="Adds openai to favorite openai list",
     *      description="Returns favorite openai id list of current user.",
     *      security={{ "passport": {} }},
     *      @OA\Parameter(
     *         name="openai_id",
     *         in="query",
     *         required=true,
     *         description="OpenAI ID",
     *         @OA\Schema(
     *             type="integer",
     *             example=0
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=412,
     *          description="Precondition Failed",
     *      ),
     * )
    */
    public function addToFavoriteOpenaiList(Request $request) {

        if($request->openai_id == null) return response()->json(['error' => 'OpenAI ID is required'], 412);

        $openAi = OpenAIGenerator::where([['id', '=', $request->openai_id]])->first();
        if($openAi == null) return response()->json(['error' => 'OpenAI not found'], 412);

        $userId=Auth::user()->id;

        $favorite = new UserFavorite();
        $favorite->user_id = $userId;
        $favorite->openai_id = $openAi->id;
        $favorite->save();

        $favoriteList = UserFavorite::where([['user_id', '=', $userId]])->pluck('openai_id')->toArray();
        return response()->json($favoriteList, 200);

    }


    /**
     * Remove from favorite openai list
     *
     * @OA\Post(
     *      path="/api/aiwriter/favorite-openai-list-remove",
     *      operationId="removeFromFavoriteOpenaiList",
     *      tags={"AI Writer"},
     *      summary="Removes openai from favorite openai list",
     *      description="Returns favorite openai id list of current user.",
     *      security={{ "passport": {} }},
     *      @OA\Parameter(
     *         name="openai_id",
     *         in="query",
     *         required=true,
     *         description="OpenAI ID",
     *         @OA\Schema(
     *             type="integer",
     *             example=0
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found",
     *      ),
     *      @OA\Response(
     *          response=412,
     *          description="Precondition Failed",
     *      ),
     * )
    */
    public function removeFromFavoriteOpenaiList(Request $request) {

        if($request->openai_id == null) return response()->json(['error' => 'OpenAI ID is required'], 412);

        $openAi = OpenAIGenerator::where([['id', '=', $request->openai_id]])->first();
        if($openAi == null) return response()->json(['error' => 'OpenAI not found'], 412);

        $userId=Auth::user()->id;

        $isFavorite = UserFavorite::where([['user_id', '=', $userId], ['openai_id', '=', $openAi->id]])->first();
        if($isFavorite == null) return response()->json(['error' => 'OpenAI is not in your favorite list'], 404);

        $isFavorite->delete();

        $favoriteList = UserFavorite::where([['user_id', '=', $userId]])->pluck('openai_id')->toArray();
        return response()->json($favoriteList, 200);

    }













}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;

use App\Models\Setting;
use App\Models\SettingTwo;
use App\Models\PdfData;
use App\Services\VectorService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Str;

use Spatie\PdfToText\Pdf;
use Illuminate\Http\File;

class ChatPdfController extends Controller
{
    protected $client;
    protected $settings;
    protected $pinecone;

    public function __construct()
    {
        //Settings
        $this->settings = Setting::first();
        $this->settings_two = SettingTwo::first();
        $apiKeys = explode(',', $this->settings->openai_api_secret);
        $apiKey = $apiKeys[array_rand($apiKeys)];
        config(['openai.api_key' => $apiKey]);

        ini_set('max_execution_time', 110000);
    }

    public function uploadPDF(Request $request)
    {

        // if ($request->hasFile('pdf')) {
        $pdf = $request->file('pdf');
        $pdf_content = file_get_contents($pdf->getRealPath());
        $fileName = Str::random(12) . ".pdf";
        Storage::disk('public')->put("temp.pdf", $pdf_content);
        Storage::disk('public')->put($fileName, $pdf_content);

        $uploadedFile = new File(substr("/uploads/$fileName", 1));


        $resPath = "/uploads/$fileName";

        if (SettingTwo::first()->ai_image_storage == "s3") {
            try {
                $aws_path = Storage::disk('s3')->put('', $uploadedFile);
                unlink(substr("/uploads/$fileName", 1));
                $resPath = Storage::disk('s3')->url($aws_path);
            } catch (\Exception $e) {
                return response()->json(["status" => "error", "message" => "AWS Error - " . $e->getMessage()]);
            }
        }

        $chat_id = $request->chat_id;

        PdfData::where('chat_id', $chat_id)->delete();

        // $text = Pdf::getText('uploads/temp.pdf');
        $parser = new \Smalot\PdfParser\Parser();
        $text = $parser->parseFile('uploads/temp.pdf')->getText();

        $page = $text;
        if (!mb_check_encoding($text, 'UTF-8')) {
            $page = mb_convert_encoding($text, 'UTF-8', mb_detect_encoding($text));
        } else {
            $page = $text;
        }

        $countwords = strlen($page) / 500 + 1;
        for ($i = 0; $i < $countwords; $i++) {
            if (500 * $i + 1000 > strlen($page)) {
                try {
                    $subtxt = substr($page, 500 * $i, strlen($page) - 500 * $i);
                    $subtxt = mb_convert_encoding($subtxt, 'UTF-8', 'UTF-8');
                    $subtxt = iconv('UTF-8', 'UTF-8//IGNORE', $subtxt);
                    $response = OpenAI::embeddings()->create([
                        'model' => 'text-embedding-ada-002',
                        'input' => $subtxt,
                    ]);

                    if (strlen(substr($page, 500 * $i, strlen($page) - 500 * $i)) > 10) {

                        $chatpdf = new PdfData();

                        $chatpdf->chat_id = $chat_id;
                        $chatpdf->content = substr($page, 500 * $i, strlen($page) - 500 * $i);
                        $chatpdf->vector = json_encode($response->embeddings[0]->embedding);

                        $chatpdf->save();
                    }
                } catch (Exception $e) {
                }
            } else {
                try {
                    $subtxt = substr($page, 500 * $i, 1000);
                    $subtxt = mb_convert_encoding($subtxt, 'UTF-8', 'UTF-8');
                    $subtxt = iconv('UTF-8', 'UTF-8//IGNORE', $subtxt);
                    $response = OpenAI::embeddings()->create([
                        'model' => 'text-embedding-ada-002',
                        'input' => $subtxt
                    ]);
                    if (strlen(substr($page, 500 * $i, 1000)) > 10) {
                        $chatpdf = new PdfData();

                        $chatpdf->chat_id = $chat_id;
                        $chatpdf->content = substr($page, 500 * $i, 1000);
                        $chatpdf->vector = json_encode($response->embeddings[0]->embedding);

                        $chatpdf->save();
                    }
                } catch (Exception $e) {
                }
            }
        }

        return response()->json(['filename' => $resPath]);
        // } else {
        //     return response()->json(['error' => 'No PDF file uploaded']);
        // }
    }

    public function getSimiliarContent(Request $request)
    {
        $vectorService = new VectorService();

        return response()->json(['extra_prompt' => $vectorService->getMostSimilarText($request->prompt, $request->chat_id)]);
    }
}

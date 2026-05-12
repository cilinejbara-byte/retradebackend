<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatBotController extends Controller
{
    public function ask(Request $request)
    {
        $userMessage = $request->input('message');

        // 1. التأكد من وصول الرسالة من فلاتر
        if (!$userMessage) {
            return response()->json(['reply' => 'الرسالة فارغة! تأكد من إرسال حقل باسم message'], 400);
        }

        $apiUrl = 'https://openrouter.ai/api/v1/chat/completions';
        
        try {
            // جلب المفتاح من ملف الـ env مباشرة لتفادي التعقيد
            $apiKey = env('OPENROUTER_API_KEY');

            // التحقق محلياً قبل إرسال الطلب للتأكد من قراءة المفتاح بنجاح
            if (!$apiKey) {
                return response()->json([
                    'reply' => 'خطأ في السيرفر: مفتاح OPENROUTER_API_KEY غير معرف في ملف الـ env.'
                ], 500);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($apiUrl, [
                'model' => 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
                'messages' => [
                    ['role' => 'user', 'content' => $userMessage]
                ],
            ]);

            // 2. التحقق من نجاح الاتصال بـ OpenRouter أولاً
            if ($response->failed()) {
                Log::error('OpenRouter Error: ' . $response->body());
                // إرجاع تفاصيل الخطأ القادم من OpenRouter مباشرة للمساعدة في التشخيص
                return response()->json([
                    'reply' => 'خطأ من OpenRouter: ' . $response->body()
                ], 500);
            }

            $data = $response->json();

            // 3. التحقق الآمن من وجود نص الرد
            if (isset($data['choices'][0]['message']['content'])) {
                return response()->json([
                    'reply' => $data['choices'][0]['message']['content']
                ]);
            }

            return response()->json([
                'reply' => 'وصل الطلب بنجاح ولكن لم يتم العثور على رد مناسب في حزمة البيانات.'
            ], 500);

        } catch (\Exception $e) {
            Log::error('ChatBot Exception: ' . $e->getMessage());
            return response()->json([
                'reply' => 'خطأ داخلي غير متوقع في السيرفر: ' . $e->getMessage()
            ], 500);
        }
    }
}

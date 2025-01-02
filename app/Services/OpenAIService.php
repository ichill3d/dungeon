<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
    }

//    public function generateChatResponse($messages, $max_tokens = 100)
//    {
//        try {
//            // Send a POST request to the OpenAI API
//            $response = Http::withHeaders([
//                'Content-Type' => 'application/json',
//                'Authorization' => 'Bearer ' . $this->apiKey,
//            ])->post('https://api.openai.com/v1/chat/completions', [
//                'model' => 'gpt-4o', // Use a valid model like gpt-4 or gpt-3.5-turbo
//                'messages' => $messages,
//                'max_tokens' => $max_tokens,
//               'response_format' => ['type'=> 'json_object']  // Ensure valid API formatting
//            ]);
//
//            // Check if the response is successful
//            if ($response->successful()) {
//                $responseData = $response->json();
//                return $responseData['choices'][0]['message']['content'] ?? 'No content returned.';
//            }
//
//            // Handle unsuccessful API responses
//            return 'Error: ' . $response->status() . ' - ' . $response->body();
//
//        } catch (\Exception $e) {
//            // Catch any exceptions and return the error message
//            return 'Exception: ' . $e->getMessage();
//        }
//
//    }


    public function generateChatResponse($messages, $max_tokens = 100, $jsonResponse = false)
    {
        try {
            // Build the payload dynamically
            $payload = [
                'model' => 'gpt-4o', // Use a valid model like gpt-4 or gpt-3.5-turbo
                'messages' => $messages,
                'max_tokens' => $max_tokens,
            ];

            // Conditionally add 'response_format' if $jsonResponse is true
            if ($jsonResponse) {
                $payload['response_format'] = ['type'=> 'json_object'];
            }

            // Send a POST request to the OpenAI API
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->post('https://api.openai.com/v1/chat/completions', $payload);

            // Check if the response is successful
            if ($response->successful()) {
                $responseData = $response->json();



                return $responseData['choices'][0]['message']['content'] ?? 'No content returned.';
            }

            // Handle unsuccessful API responses
            return 'Error: ' . $response->status() . ' - ' . $response->body();

        } catch (\Exception $e) {
            // Catch any exceptions and return the error message
            return 'Exception: ' . $e->getMessage();
        }
    }


}

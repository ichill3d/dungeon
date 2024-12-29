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

    public function generateChatResponse($messages, $max_tokens = 100)
    {
        // You can dynamically fetch the ngrok URL from the .env file
        $ngrokUrl = env('NGROK_URL'); // Make sure NGROK_URL is set in your .env file

        // Send a POST request to the OpenAI API via your ngrok tunnel
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini', // You can use other models, e.g., gpt-3.5-turbo
            'messages' => $messages,
            'max_tokens' => $max_tokens,
            'store' => true, // Optional based on API capabilities
        ]);

        return $response->json()['choices'][0]['message']['content'];

        // Handle errors
        return 'Error: Unable to generate response. ' . $response->status() . ' - ' . $response->body();
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiChatService
{
    /**
     * Gọi Chat Completions (có thể kèm tools). Trả về body JSON đầy đủ.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function chatCompletion(array $payload): array
    {
        $key = config('services.openai.api_key');
        if (! is_string($key) || $key === '') {
            throw new \RuntimeException('OpenAI API key not configured.');
        }

        $model = config('services.openai.model', 'gpt-4o-mini');

        $body = array_merge([
            'model' => $model,
            'max_tokens' => (int) config('services.openai.max_tokens', 1024),
            'temperature' => (float) config('services.openai.temperature', 0.7),
        ], $payload);

        $response = Http::timeout(120)
            ->withToken($key)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', $body);

        if (! $response->successful()) {
            Log::warning('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('OpenAI request failed.');
        }

        return $response->json();
    }

    /** @deprecated Dùng chatCompletion + tool loop trong controller */
    public function chat(array $messages): string
    {
        $data = $this->chatCompletion(['messages' => $messages]);
        $content = data_get($data, 'choices.0.message.content');

        return is_string($content) ? $content : '';
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * عقد حذف موحّد (QA الدفعة 5): يقبل ids أو id، قيمة مفردة أو مصفوفة
     */
    protected function requestIds(Request $request): array
    {
        $raw = $request->input('ids') ?? $request->input('id') ?? [];

        return array_values(array_unique(array_filter(array_map('intval', (array) $raw))));
    }

    /**
     * رفض حذف برسالة عربية (تعرضها كل شاشات القوائم عبر معالج ajaxError العام)
     */
    protected function deleteBlocked(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'status' => 'error', 'message' => $message], 422);
    }
}

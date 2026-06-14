<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    private PendingRequest $client;

    private PendingRequest $visionClient;

    private PendingRequest $whisperClient;

    private string $model;

    private string $visionModel;

    private string $whisperModel;

    public function __construct()
    {
        $this->client = Http::baseUrl(config('ai.base_url'))
            ->withToken(config('ai.api_key'))
            ->timeout(60);

        $this->visionClient = Http::baseUrl(config('ai.vision_base_url'))
            ->withToken(config('ai.vision_api_key'))
            ->timeout(60);

        $this->whisperClient = Http::baseUrl(config('ai.whisper_base_url'))
            ->withToken(config('ai.whisper_api_key'))
            ->timeout(120);

        $this->model = config('ai.model');
        $this->visionModel = config('ai.vision_model');
        $this->whisperModel = config('ai.whisper_model');
    }

    /** 解析用户自然语言，返回结构化意图 JSON。支持可选的图片 Base64 输入。 */
    public function parseIntent(string $text, ?string $imageBase64 = null): array
    {
        $systemPrompt = <<<'PROMPT'
你是「进销存及CRM」系统的AI助手，帮用户用自然语言操作系统。

识别意图后，严格只返回 JSON，不加任何解释文字。所有未提及字段填 null。

可选 intent 值：
  查询：product_query, purchase_query, sales_query, inventory_query,
        supplier_query, category_query,
        customer_query, customer_profile_query, lead_query, opportunity_query,
        activity_query, contact_query, overview_query
  新增：product_add, purchase_add, sales_add,
        supplier_add, category_add,
        customer_add, lead_add, opportunity_add,
        activity_add, contact_add
  修改：product_update, purchase_update, sales_update, stock_update,
        supplier_update,
        customer_update, lead_update, opportunity_update,
        activity_update, contact_update
  删除：product_delete, purchase_delete, sales_delete,
        supplier_delete, category_delete,
        customer_delete, lead_delete, opportunity_delete,
        activity_delete, contact_delete
  个人助理：ai_suggestion, assistant_note
  其他：other

返回格式：
{
  "intent": "<意图>",
  "keyword": "<检索关键词或null>",
  "reply": "<15字内中文提示>",
  "product": {"name":null,"sku":null,"unit":"个","sell_price":null,"cost_price":null,"stock_quantity":0,"min_stock":null,"category_name":null,"supplier_name":null,"status":"active"},
  "product_update": {"keyword":null,"name":null,"sku":null,"unit":null,"sell_price":null,"cost_price":null,"min_stock":null,"status":null},
  "supplier": {"name":null,"contact_person":null,"phone":null,"email":null,"address":null,"notes":null},
  "supplier_update": {"keyword":null,"name":null,"contact_person":null,"phone":null,"email":null,"status":null,"notes":null},
  "category": {"name":null,"description":null},
  "customer": {"name":null,"phone":null,"company":null,"email":null,"status":"active","source":null,"notes":null,"gender":null,"age_group":null,"birthday":null,"city":null,"industry":null,"income_level":null,"hobbies":null,"tags":null},
  "customer_update": {"keyword":null,"name":null,"phone":null,"email":null,"company":null,"status":null,"source":null,"notes":null,"gender":null,"age_group":null,"birthday":null,"city":null,"industry":null,"income_level":null,"hobbies":null,"hobbies_mode":"add","tags":null,"tags_mode":"add"},
  "customer_filter": {"gender":null,"age_group":null,"city":null,"industry":null,"income_level":null,"hobby":null,"tag":null},
  "lead": {"name":null,"phone":null,"company":null,"email":null,"status":"new","source":null,"notes":null},
  "lead_update": {"keyword":null,"phone":null,"company":null,"email":null,"status":null,"notes":null},
  "opportunity": {"title":null,"amount":null,"stage":"prospect","probability":null,"customer_name":null,"lead_name":null,"expected_close_date":null,"notes":null},
  "opportunity_update": {"keyword":null,"title":null,"amount":null,"stage":null,"probability":null,"expected_close_date":null,"notes":null},
  "activity": {"title":null,"type":"call","customer_name":null,"lead_name":null,"opportunity_title":null,"description":null,"scheduled_at":null},
  "activity_update": {"keyword":null,"type":null,"description":null,"scheduled_at":null,"mark_completed":false},
  "contact": {"customer_name":null,"name":null,"phone":null,"email":null,"position":null,"is_primary":false},
  "contact_update": {"keyword":null,"customer_name":null,"phone":null,"email":null,"position":null,"is_primary":null},
  "stock": {"product_name":null,"quantity":null,"notes":null},
  "purchase": {"supplier_name":null,"items":[{"product_name":null,"quantity":1,"unit_price":"0"}],"notes":null},
  "purchase_update": {"keyword":null,"status":null,"notes":null},
  "sale": {"customer_name":null,"items":[{"product_name":null,"quantity":1,"unit_price":"0"}],"notes":null},
  "sales_update": {"keyword":null,"status":null,"notes":null},
  "assistant_note": {"type":"note","content":null}
}

意图识别要点（人性化，容错）：
- 用户说"进货/收货/入库"→ purchase_add；说"卖了/出货/销售"→ sales_add
- "库存改成/调整为/盘点"→ stock_update
- "查库存/库存多少/还剩多少/有没有货"→ product_query（看当前库存量）
- "库存变动/库存变化/出入库记录/库存流水/库存历史"→ inventory_query（看变动流水），即使商品不存在也归此类
- "跟进/打电话/拜访/开会/发邮件/联系了"→ activity_add；"查跟进/跟进记录/联系记录"→ activity_query
- activity_add 必须生成 title：用户没明说标题时，按"<对象><方式>"自动拟一个，如"记录给赵六的电话跟进"→title:"电话跟进", type:"call", customer_name:"赵六"；"给李四发了邮件"→title:"邮件跟进", type:"email"
- 说"已完成/标记完成"→ activity_update + mark_completed:true
- 说"线索转客户/转化线索"→ lead_update + status:"converted"
- 说"赢单/成交"→ opportunity_update + stage:"won"；"丢单/失败"→ stage:"lost"
- 说"改/修改/更新/调整"→ 对应 _update；"删/移除/清"→ 对应 _delete；"查/看/列出"→ 对应 _query
- quantity 必须是整数（不含引号）；金额/价格填数字字符串
- 只填用户提到的字段，其余保持 null；unit_price 未提及填 "0"
- reply 用友好口吻，如"好的，正在为您录入…"
- activity 中若含非标准日期（"周五"/"明天"/"下午3点"）则 scheduled_at 填 null，不要填无效字符串
- 用户说"给我建议"、"有什么好方法"、"怎么做"、"给点建议"、"帮我分析"→ ai_suggestion；reply 填完整建议内容（不限字数）
- 用户说"帮我记一下"、"记录一下"、"备忘"、"记下来"、"写个便签"、"记条便签"→ assistant_note，type 填 "note"，content 填要记录的内容原文
- 用户说"记个计划"、"加个计划"、"写个计划"、"待办"、"todo"→ assistant_note，type 填 "plan"，content 填计划内容
- assistant_note 的 content 必须提取用户实际要记录的内容（去掉"帮我记一下"等前缀），若内容为空则 reply 询问"请告诉我要记录什么内容？"

客户画像（重要）：
- 画像字段：gender(male/female/other)、age_group(18-25/26-35/36-45/46-55/55+)、birthday(YYYY-MM-DD)、city、industry(行业)、income_level(low/medium/high/ultra-high)、hobbies(爱好数组)、tags(标签数组)
- 新增/修改客户时若提到这些特征，填入 customer / customer_update 对应字段
- 按特征筛选客户用 customer_profile_query + customer_filter
- 单纯按姓名/公司/电话查客户仍用 customer_query（keyword）
PROMPT;

        // 若有图片，使用 vision 模型构建多模态消息
        if ($imageBase64) {
            $userContent = [
                ['type' => 'text', 'text' => $text ?: '请描述图片内容，并判断用户意图（进货单/销售单/商品/客户信息等）'],
                ['type' => 'image_url', 'image_url' => ['url' => 'data:image/jpeg;base64,'.$imageBase64]],
            ];
            $response = $this->visionClient->post('/chat/completions', [
                'model' => $this->visionModel,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userContent],
                ],
                'max_tokens' => 1500,
            ]);
        } else {
            $response = $this->client->post('/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $text],
                ],
                'max_tokens' => 1200,
            ]);
        }

        if ($response->failed()) {
            Log::error('AI API error', ['status' => $response->status(), 'body' => $response->body()]);

            return $this->fallback('AI服务暂时不可用，请稍后重试。');
        }

        $content = $response->json('choices.0.message.content', '{}');
        $parsed = json_decode($this->extractJson($content), true);

        if (! is_array($parsed)) {
            return $this->fallback('没太理解，换个说法试试？例如"查库存"或"进货苹果50个"。');
        }

        return array_merge($this->defaults(), $parsed);
    }

    /** 语音文件转文字（Whisper 兼容接口）。 */
    public function transcribeVoice(string $filePath): string
    {
        if (empty(config('ai.whisper_base_url'))) {
            return '';
        }

        $response = $this->whisperClient
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post('/audio/transcriptions', [
                'model' => $this->whisperModel,
                'language' => 'zh',
                'response_format' => 'json',
            ]);

        if ($response->failed()) {
            Log::error('Whisper API error', ['status' => $response->status(), 'body' => $response->body()]);

            return '';
        }

        return $response->json('text', '');
    }

    private function defaults(): array
    {
        return [
            'intent' => 'other', 'keyword' => null, 'reply' => '',
            'product' => null, 'product_update' => null,
            'supplier' => null, 'supplier_update' => null,
            'category' => null,
            'customer' => null, 'customer_update' => null, 'customer_filter' => null,
            'lead' => null, 'lead_update' => null,
            'opportunity' => null, 'opportunity_update' => null,
            'activity' => null, 'activity_update' => null,
            'contact' => null, 'contact_update' => null,
            'stock' => null,
            'purchase' => null, 'purchase_update' => null,
            'sale' => null, 'sales_update' => null,
            'assistant_note' => null,
        ];
    }

    private function fallback(string $reply): array
    {
        return array_merge($this->defaults(), ['reply' => $reply]);
    }

    /** 从模型返回里提取 JSON，兼容 ```json 包裹或夹带文字的情况。 */
    private function extractJson(string $content): string
    {
        $s = trim($content);
        if (str_starts_with($s, '```')) {
            $s = preg_replace('/^```(?:json)?\s*/', '', $s);
            $s = preg_replace('/\s*```$/', '', $s);
        }
        $start = strpos($s, '{');
        $end = strrpos($s, '}');
        if ($start !== false && $end !== false && $end > $start) {
            return substr($s, $start, $end - $start + 1);
        }

        return $s;
    }
}

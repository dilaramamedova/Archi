<?php

namespace Database\Seeders;

use App\Models\FaqQuestion;
use App\Models\FaqTopic;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        // Pull a 'help' group translation row as a translatable array (az/ru/en).
        $val = function (string $key): array {
            $row = Translation::where('group', 'help')->where('key', $key)->first();

            return [
                'az' => $row?->getTranslation('value', 'az', false) ?? '',
                'ru' => $row?->getTranslation('value', 'ru', false) ?? '',
                'en' => $row?->getTranslation('value', 'en', false) ?? '',
            ];
        };

        // slug => [titleKey, descKey, icon]
        $topics = [
            'orders'      => ['topic_orders', 'topic_orders_desc', '📦'],
            'delivery'    => ['topic_delivery', 'topic_delivery_desc', '🚚'],
            'returns'     => ['topic_returns', 'topic_returns_desc', '💰'],
            'payment'     => ['topic_payment', 'topic_payment_desc', '💳'],
            'security'    => ['topic_security', 'topic_security_desc', '🔒'],
            'specialists' => ['topic_specialists', 'topic_specialists_desc', '🔧'],
        ];

        $topicIds = [];
        $sort = 0;
        foreach ($topics as $slug => [$titleKey, $descKey, $icon]) {
            $topic = FaqTopic::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $val($titleKey),
                    'description' => $val($descKey),
                    'icon' => $icon,
                    'sort_order' => $sort++,
                    'is_active' => true,
                ],
            );
            $topicIds[$slug] = $topic->id;
        }

        // faqN => [questionKey, answerKey, topicSlug]
        $questions = [
            'faq1' => ['faq1_q', 'faq1_a', 'orders'],
            'faq2' => ['faq2_q', 'faq2_a', 'delivery'],
            'faq3' => ['faq3_q', 'faq3_a', 'returns'],
            'faq4' => ['faq4_q', 'faq4_a', 'payment'],
            'faq5' => ['faq5_q', 'faq5_a', 'specialists'],
            'faq6' => ['faq6_q', 'faq6_a', 'security'],
        ];

        $qSort = 0;
        foreach ($questions as [$qKey, $aKey, $topicSlug]) {
            FaqQuestion::updateOrCreate(
                ['faq_topic_id' => $topicIds[$topicSlug]],
                [
                    'question' => $val($qKey),
                    'answer' => $val($aKey),
                    'sort_order' => $qSort++,
                    'is_active' => true,
                ],
            );
        }
    }
}

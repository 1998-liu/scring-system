<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\ScoringRule;
use App\ScoringRuleWeight;
use App\ScoringLevel;

class ScoringSystemSeeder extends Seeder
{
    public function run()
    {
        $rules = [
            [
                'name' => '中层正职评分规则',
                'target_role' => '中层正职',
                'description' => '中层正职综合得分 = 正职领导评议均分×50% + 公司各版块分管领导评议均分×40% + 管理人员评议均分×10%',
                'weights' => [
                    ['evaluator_role' => '正职领导', 'weight' => 50],
                    ['evaluator_role' => '中层正职', 'weight' => 40],
                    ['evaluator_role' => '管理人员', 'weight' => 10]
                ]
            ],
            [
                'name' => '中层副职及助理评分规则',
                'target_role' => '中层副职及助理',
                'description' => '中层副职及助理综合得分 = 正职领导评议均分×50% + 中层正职评议均分×40% + 管理人员评议均分×10%',
                'weights' => [
                    ['evaluator_role' => '正职领导', 'weight' => 50],
                    ['evaluator_role' => '中层正职', 'weight' => 40],
                    ['evaluator_role' => '管理人员', 'weight' => 10]
                ]
            ],
            [
                'name' => '管理人员评分规则',
                'target_role' => '管理人员',
                'description' => '管理人员综合得分 = 正职领导评议均分×40% + 中层正职评议均分×30% + 中层副职及助理评议均分×20% + 管理人员评议均分×10%',
                'weights' => [
                    ['evaluator_role' => '正职领导', 'weight' => 40],
                    ['evaluator_role' => '中层正职', 'weight' => 30],
                    ['evaluator_role' => '中层副职及助理', 'weight' => 20],
                    ['evaluator_role' => '管理人员', 'weight' => 10]
                ]
            ]
        ];

        foreach ($rules as $ruleData) {
            $rule = ScoringRule::create([
                'name' => $ruleData['name'],
                'target_role' => $ruleData['target_role'],
                'description' => $ruleData['description'],
                'is_active' => true
            ]);

            foreach ($ruleData['weights'] as $weightData) {
                ScoringRuleWeight::create([
                    'scoring_rule_id' => $rule->id,
                    'evaluator_role' => $weightData['evaluator_role'],
                    'weight' => $weightData['weight']
                ]);
            }
        }

        $levels = [
            ['name' => '优', 'min_score' => 95, 'max_score' => 100, 'color' => '#67C23A', 'sort_order' => 1],
            ['name' => '称职', 'min_score' => 80, 'max_score' => 94.99, 'color' => '#409EFF', 'sort_order' => 2],
            ['name' => '基本称职', 'min_score' => 60, 'max_score' => 79.99, 'color' => '#E6A23C', 'sort_order' => 3],
            ['name' => '不称职', 'min_score' => 0, 'max_score' => 59.99, 'color' => '#F56C6C', 'sort_order' => 4]
        ];

        foreach ($levels as $levelData) {
            ScoringLevel::create([
                'name' => $levelData['name'],
                'min_score' => $levelData['min_score'],
                'max_score' => $levelData['max_score'],
                'color' => $levelData['color'],
                'sort_order' => $levelData['sort_order'],
                'is_active' => true
            ]);
        }

        $this->command->info('评分规则和评分等级初始化完成');
    }
}

<?php 
$svc = app(\App\Services\DashboardInsightsService::class); 
$reflection = new ReflectionClass($svc);
$method = $reflection->getMethod('chartData');
$method->setAccessible(true);
dump($method->invokeArgs($svc, [2026, ['starts_at' => '2026-03-10', 'ends_at' => '2026-03-23', 'source' => 'test', 'label' => 'test'], false]));

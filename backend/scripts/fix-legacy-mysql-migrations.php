<?php

declare(strict_types=1);

function fixLegacyMysqlMigrations(string $backendPath): array
{
    $targets = [
        $backendPath . '/database/migrations/2026_03_17_020100_create_tse_cache_table.php',
        $backendPath . '/database/migrations/2026_03_17_030000_create_consulta_historicos_table.php',
    ];

    $replacements = [
        "\$table->timestamp('consultado_em');" => "\$table->dateTime('consultado_em');",
        "\$table->timestamp('expira_em');" => "\$table->dateTime('expira_em');",
        '$table->timestamps();' => "\$table->dateTime('created_at')->nullable();\n            \$table->dateTime('updated_at')->nullable();",
    ];

    $report = [
        'checked' => [],
        'changed' => [],
        'errors' => [],
    ];

    foreach ($targets as $filePath) {
        $report['checked'][] = $filePath;

        if (!is_file($filePath) || !is_readable($filePath)) {
            $report['errors'][] = "Arquivo não encontrado: {$filePath}";
            continue;
        }

        $content = file_get_contents($filePath);
        if (!is_string($content)) {
            $report['errors'][] = "Falha ao ler: {$filePath}";
            continue;
        }

        $updated = str_replace(array_keys($replacements), array_values($replacements), $content);
        if ($updated !== $content) {
            $written = file_put_contents($filePath, $updated);
            if ($written === false) {
                $report['errors'][] = "Falha ao gravar: {$filePath}";
                continue;
            }
            $report['changed'][] = $filePath;
        }
    }

    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }

    return $report;
}

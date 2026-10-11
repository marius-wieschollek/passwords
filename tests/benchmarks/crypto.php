<?php
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud Passwords contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

// Usage: php tests/benchmarks/crypto.php /path/to/nextcloud
error_reporting(0);
ob_start();

trait SyntheticKeyDerivationCounter {
    public int $calls = 0;
    public float $seconds = 0;

    public function setPassword($password, $method = 'pbkdf2', ...$arguments) {
        $start = hrtime(true);
        $result = parent::setPassword($password, $method, ...$arguments);
        $this->seconds += (hrtime(true) - $start) / 1e9;
        $this->calls++;
        return $result;
    }
}

function createSyntheticCrypto(): array {
    // No Nextcloud bootstrap, database access or instance configuration.
    $config = new class extends \OC\AllConfig {
        public function __construct() {
        }

        public function getSystemValue($key, $default = '') {
            return $default;
        }

        public function getSystemValueString(string $key, string $default = ''): string {
            return $default;
        }
    };
    $crypto = new \OC\Security\Crypto($config);
    if (class_exists(\phpseclib3\Crypt\AES::class)) {
        $cipher = new class('cbc') extends \phpseclib3\Crypt\AES {
            use SyntheticKeyDerivationCounter;
        };
    } else {
        $cipher = new class extends \phpseclib\Crypt\AES {
            use SyntheticKeyDerivationCounter;
        };
    }
    (new ReflectionProperty(\OC\Security\Crypto::class, 'cipher'))->setValue($crypto, $cipher);
    return [$crypto, $cipher];
}

try {
    $root = realpath($argv[1] ?? '');
    if ($root === false || !is_file($root . '/lib/private/Security/Crypto.php')) {
        throw new RuntimeException('nextcloud_root_required');
    }
    require_once $root . '/lib/composer/autoload.php';
    require_once $root . '/3rdparty/autoload.php';

    $report = [
        'php_version' => PHP_VERSION,
        'crypto_sha256' => hash_file('sha256', $root . '/lib/private/Security/Crypto.php'),
        'data' => 'synthetic only',
        'fields_per_revision' => 6,
        'samples' => [],
    ];
    [$writer] = createSyntheticCrypto();
    foreach ([1, 100, 342] as $count) {
        $fixtures = [];
        for ($revision = 0; $revision < $count; $revision++) {
            $key = str_repeat('Synthetic key material / ', 128) . $revision;
            $fields = [];
            for ($field = 0; $field < 6; $field++) {
                $fields[] = $writer->encrypt('Synthetic field ' . $field, $key);
            }
            $fixtures[] = [$key, $fields];
        }

        $runs = [];
        for ($round = 0; $round < 3; $round++) {
            [$reader, $cipher] = createSyntheticCrypto();
            $start = hrtime(true);
            foreach ($fixtures as [$key, $fields]) {
                foreach ($fields as $field => $value) {
                    if ($reader->decrypt($value, $key) !== 'Synthetic field ' . $field) {
                        throw new RuntimeException('fixture_mismatch');
                    }
                }
            }
            $runs[] = [
                'wall_ms' => round((hrtime(true) - $start) / 1e6, 2),
                'set_password_calls' => $cipher->calls,
                'set_password_ms' => round($cipher->seconds * 1000, 2),
            ];
        }
        $report['samples'][] = ['revisions' => $count, 'runs' => $runs];
    }
    $report['fixture_comparisons_passed'] = true;
    ob_end_clean();
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
} catch (Throwable $error) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    // Suppress exception arguments and traces; output contains no keys or plaintexts.
    echo json_encode(['error' => 'benchmark_failed']) . "\n";
    exit(1);
}

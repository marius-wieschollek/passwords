# Passwords crypto benchmark

```sh
php tests/benchmarks/crypto.php /path/to/nextcloud
```

Requires a Nextcloud source tree with its `3rdparty` submodule. Supports phpseclib 2 and 3. It does not bootstrap Nextcloud, read instance configuration, access a database or use vault data.

The benchmark encrypts six synthetic fields per revision, then checks every decrypted value. For 1, 100 and 342 revisions, it reports three cold-reader runs: elapsed time, `AES::setPassword()` calls and time spent in that method. No keys or plaintexts are printed. The source hash identifies the tested `Crypto.php`.

Passwords decrypts its six SSE fields separately with the same revision key. A Nextcloud 34.0.4 measurement found 2,040 derivations for 340 visible revisions, accounting for 91–94% of hydration/decryption time. Reusing the last derived key in Nextcloud could reduce this to one derivation per revision. Run against both versions to compare; this benchmark does not measure the HTTP endpoint or SQL queries.

This contribution was made using AI. This is a draft for human review and validation.

<?php

use App\Models\UploadedSchema;
use App\Models\AnalysisReport;
use App\Services\SchemaParserService;
use App\Services\AnalyzerService;
use App\Services\ErGeneratorService;
use App\Services\MigrationDocService;
use App\Jobs\AnalyzeDatabaseJob;

test('SchemaParserService parses DDL correctly', function () {
    $sql = "
        CREATE TABLE users (
            id BIGINT PRIMARY KEY,
            nama VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL
        );

        CREATE TABLE penjualan (
            id BIGINT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            total DECIMAL(10,2),
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
    ";

    $parser = new SchemaParserService();
    $parsed = $parser->parse($sql);

    expect($parsed)->toHaveKey('users');
    expect($parsed)->toHaveKey('penjualan');
    expect($parsed['penjualan']['foreign_keys'])->toHaveCount(1);
    expect($parsed['penjualan']['foreign_keys'][0]['column'])->toBe('user_id');
});

test('AnalyzerService detects missing foreign key indexes', function () {
    $sql = "
        CREATE TABLE detail_penjualan (
            id BIGINT PRIMARY KEY,
            produk_id BIGINT NOT NULL
        );
    ";

    $parser = new SchemaParserService();
    $parsed = $parser->parse($sql);
    $parsed['detail_penjualan']['foreign_keys'] = [
        ['column' => 'produk_id', 'references_table' => 'produk', 'references_column' => 'id']
    ];

    $analyzer = new AnalyzerService();
    $analysis = $analyzer->analyze($parsed);

    expect($analysis['potential_problems'])->toHaveCount(1);
    expect($analysis['potential_problems'][0]['column'])->toBe('produk_id');
    expect($analysis['index_recommendations'])->toHaveCount(1);
});

test('ErGeneratorService generates valid Mermaid syntax', function () {
    $sql = "
        CREATE TABLE produk (
            id BIGINT PRIMARY KEY,
            nama VARCHAR(100)
        );
    ";

    $parser = new SchemaParserService();
    $parsed = $parser->parse($sql);

    $erGen = new ErGeneratorService();
    $mermaid = $erGen->generate($parsed);

    expect($mermaid)->toContain('erDiagram');
    expect($mermaid)->toContain('produk');
});

test('API POST /api/analyze accepts raw sql and returns 202', function () {
    $sql = "CREATE TABLE test_table (id BIGINT PRIMARY KEY);";

    $response = $this->postJson('/api/analyze', [
        'raw_sql' => $sql,
    ]);

    $response->assertStatus(202);
    $response->assertJsonStructure(['schema_id', 'status', 'message']);
});

test('AnalyzeDatabaseJob processes schema successfully', function () {
    $sql = "
        CREATE TABLE users (id BIGINT PRIMARY KEY);
        CREATE TABLE penjualan (id BIGINT PRIMARY KEY, user_id BIGINT, FOREIGN KEY (user_id) REFERENCES users(id));
    ";

    $schema = UploadedSchema::create([
        'filename' => 'test.sql',
        'original_filename' => 'test.sql',
        'raw_sql' => $sql,
        'status' => 'pending',
    ]);

    $job = new AnalyzeDatabaseJob($schema->id);
    $job->handle(
        new SchemaParserService(),
        new AnalyzerService(),
        new ErGeneratorService(),
        new MigrationDocService()
    );

    $schema->refresh();
    expect($schema->status)->toBe('completed');
    expect($schema->analysisReport)->not->toBeNull();
    expect($schema->analysisReport->erd_mermaid)->toContain('erDiagram');
});



<?php

test('custom namespaces can be registered', function () {
    $this->assertSame(
        '<b-test />',
        $this->render('<b-test />')
    );

    \Stillat\Dagger\Facades\Compiler::registerComponentPath(
        'b',
        __DIR__.'/../resources/custom_namespace'
    );

    $this->assertSame(
        'From Custom Namespace: The Title',
        $this->render('<b-test title="The Title" />')
    );
});

test('custom namespaces can load from multiple paths', function () {
    $this->assertSame(
        '<b-test />',
        $this->render('<b-test />')
    );

    $this->assertSame(
        '<b-two />',
        $this->render('<b-two />')
    );

    \Stillat\Dagger\Facades\Compiler::registerComponentPath(
        'b',
        __DIR__.'/../resources/custom_namespace'
    );

    \Stillat\Dagger\Facades\Compiler::registerComponentPath(
        'b',
        __DIR__.'/../resources/custom_namespace_two'
    );

    $this->assertSame(
        'From Custom Namespace: The Title',
        $this->render('<b-test title="The Title" />')
    );

    $this->assertSame(
        'From Custom Namespace in a Different Path: The Title',
        $this->render('<b-two title="The Title" />')
    );
});

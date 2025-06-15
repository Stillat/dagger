<?php

use Stillat\Dagger\Exceptions\InvalidCompilerParameterException;

test('it throws an exception when using variable references', function () {
    $this->expectException(InvalidCompilerParameterException::class);

    $this->render('<c-root :#id="someId" />');
});

test('it throws an exception when using curly brace values', function () {
    $this->expectException(InvalidCompilerParameterException::class);

    $this->render('<c-root #id="{nope}" />');
});

test('it throws an exception when using interpolated values', function () {

    $this->expectException(InvalidCompilerParameterException::class);

    $this->render('<c-root #id="hello-{{ $there }}" />');
});

test('it throws an exception when using interpolated values 2', function () {

    $this->expectException(InvalidCompilerParameterException::class);

    $this->render('<c-root #id="hello-{{{ $there }}}" />');
});

test('it throws an exception when using interpolated values 3', function () {

    $this->expectException(InvalidCompilerParameterException::class);

    $this->render('<c-root #id="hello-{{{ $there }}}" />');
});

test('compiler params can be escaped', function () {
    $this->assertSame(
        '#id="theId" #notreserved="value" No Prop',
        $this->render('<c-attributes ##id="theId" ##notreserved="value" />')
    );
});

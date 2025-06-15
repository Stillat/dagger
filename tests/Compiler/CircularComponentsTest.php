<?php

test('it compiles circular component references', function () {
    $comments = [
        ['message' => 'Message 1'],
        ['message' => 'Message 2'],
        [
            'message' => 'Message 3',
            'comments' => [
                ['message' => 'Message 3.1'],
                [
                    'message' => 'Message 3.2',
                    'comments' => [
                        ['message' => 'Message 3.2.1'],
                        ['message' => 'Message 3.2.2'],
                        [
                            'message' => 'Message 3.2.3',
                            'comments' => [
                                ['message' => 'Message 3.2.3.1'],
                                ['message' => 'Message 3.2.3.2'],
                                ['message' => 'Message 3.2.3.3'],
                            ],
                        ],
                    ],
                ],
                ['message' => 'Message 3.3'],
            ],
        ],
    ];

    $template = <<<'BLADE'
<c-thread :$comments />
BLADE;

    $expected = <<<'EXPECTED'
<ul>
            

<li>
    <span>Message 1</span>

    </li>            

<li>
    <span>Message 2</span>

    </li>            

<li>
    <span>Message 3</span>

            

<ul>
            

<li>
    <span>Message 3.1</span>

    </li>            

<li>
    <span>Message 3.2</span>

            

<ul>
            

<li>
    <span>Message 3.2.1</span>

    </li>            

<li>
    <span>Message 3.2.2</span>

    </li>            

<li>
    <span>Message 3.2.3</span>

            

<ul>
            

<li>
    <span>Message 3.2.3.1</span>

    </li>            

<li>
    <span>Message 3.2.3.2</span>

    </li>            

<li>
    <span>Message 3.2.3.3</span>

    </li>    </ul>    </li>    </ul>    </li>            

<li>
    <span>Message 3.3</span>

    </li>    </ul>    </li>    </ul>
EXPECTED;

    $this->assertSame(
        $expected,
        $this->render($template, ['comments' => $comments])
    );
});

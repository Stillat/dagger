<?php

namespace Stillat\Dagger\Compiler;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class DisableOptimization {}

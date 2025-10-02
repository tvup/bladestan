@extends('partial.filename')

{{ $foo }}
-----
<?php

/** file: foo.blade.php, line: 3 */
echo e($foo);
echo $__env->make('partial.filename', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render();

<?php

namespace A;
    use Countable;

    class AA {}

namespace B;
    var_dump(class_exists("A\AA"));
    class BB {}

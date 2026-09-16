<?php

namespace App\Exceptions;

// izmests, ja starp pārbaudi un piešķiršanu kāds cits jau paspējis mainīt vienības stāvokli
// (piem. divi vienlaicīgi skenējumi tam pašam QR kodam)
class CostumeItemUnavailableException extends \RuntimeException
{
}

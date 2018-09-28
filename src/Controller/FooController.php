<?php

namespace App\Controller;

use App\Controller\TokenAuthenticatedController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FooController extends AbstractController implements TokenAuthenticatedController
{
 
    public function bar()
    {
        return $this->json(['Hello World']);
    }
}

<?php

namespace App\EventSubscriber;

use App\Controller\TokenAuthenticatedController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;

class TokenSubscriber implements EventSubscriberInterface
{
    private $tokens;

    public function __construct($tokens)
    {
        $this->tokens = $tokens;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }

        if ($controller[0] instanceof TokenAuthenticatedController) {
            $token = $event->getRequest()->query->get('token');
            if (!in_array($token, $this->tokens)) {
                throw new AccessDeniedHttpException('This action needs a valid token!', null, Response::HTTP_UNAUTHORIZED);
            }


            // mark the request as having passed token authentication
            $event->getRequest()->attributes->set('auth_token', $token);
        }
    }

	public function onKernelResponse(FilterResponseEvent $event)
	{
		// check to see if onKernelController marked this as a token "auth'ed" request
        if (!$token = $event->getRequest()->attributes->get('auth_token')) {
			return;
		}

		$response = $event->getResponse();

		// create a hash and set it as a response header
		$hash = sha1($response->getContent().$token);
		$response->headers->set('X-CONTENT-HASH', $hash);
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
		  // You get the exception object from the received event
        $exception = $event->getException();
        $message = sprintf(
            'My Error says: %s with code: %s',
            $exception->getMessage(),
            $exception->getCode()
        );

        // Customize your response object to display the exception details
        $response = new Response();
        $response->setContent($message);

        // HttpExceptionInterface is a special type of exception that
        // holds status code and header details
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // sends the modified response object to the event
        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
	{
        return array(
            KernelEvents::CONTROLLER => 'onKernelController',
			KernelEvents::RESPONSE => 'onKernelResponse',
			KernelEvents::EXCEPTION => 'onKernelException',
        );
    }
}

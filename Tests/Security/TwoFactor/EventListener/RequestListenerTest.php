<?php
namespace Scheb\TwoFactorBundle\Tests\Security\TwoFactor\EventListener;

use Scheb\TwoFactorBundle\Security\TwoFactor\EventListener\RequestListener;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class RequestListenerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $securityContext;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var \Scheb\TwoFactorBundle\Security\TwoFactor\EventListener\RequestListener
     */
    private $listener;

    public function setUp()
    {
        $this->provider = $this->getMockBuilder("Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProvider")
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext = $this->getMock("Symfony\Component\Security\Core\SecurityContextInterface");

        $supportedTokens = array("Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken");
        $this->listener = new RequestListener($this->provider, $this->securityContext, $supportedTokens);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createEvent()
    {
        $this->request = $this->getMock("Symfony\Component\HttpFoundation\Request");
        $event = $this->getMockBuilder("Symfony\Component\HttpKernel\Event\GetResponseEvent")
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->expects($this->any())
            ->method("getRequest")
            ->will($this->returnValue($this->request));

        return $event;
    }

    private function stubSecurityContext($token)
    {
        $this->securityContext
            ->expects($this->any())
            ->method("getToken")
            ->will($this->returnValue($token));
    }

    /**
     * @test
     */
    public function onCoreRequest_tokenClassSupported_requestAuthenticationCode()
    {
        $event = $this->createEvent();
        $token = new UsernamePasswordToken("user", array(), "key");
        $this->stubSecurityContext($token);

        //Expect TwoFactorProvider to be called
        $this->provider
            ->expects($this->once())
            ->method("requestAuthenticationCode")
            ->with($this->request, $token);

        $this->listener->onCoreRequest($event);
    }

    /**
     * @test
     */
    public function onCoreRequest_responseCreated_setResponseOnEvent()
    {
        $event = $this->createEvent();
        $token = new UsernamePasswordToken("user", array(), "key");
        $this->stubSecurityContext($token);
        $response = $this->getMock("Symfony\Component\HttpFoundation\Response");

        //Stub the TwoFactorProvider
        $this->provider
            ->expects($this->any())
            ->method("requestAuthenticationCode")
            ->will($this->returnValue($response));

        //Expect response to be set
        $event
            ->expects($this->once())
            ->method("setResponse")
            ->with($response);

        $this->listener->onCoreRequest($event);
    }

    /**
     * @test
     */
    public function onCoreRequest_tokenClassNotSupported_doNothing()
    {
        $event = $this->createEvent();
        $token = $this->getMock("Symfony\Component\Security\Core\Authentication\Token\TokenInterface");
        $this->stubSecurityContext($token);

        //Stub the TwoFactorProvider
        $this->provider
            ->expects($this->never())
            ->method("requestAuthenticationCode");

        $this->listener->onCoreRequest($event);
    }

}
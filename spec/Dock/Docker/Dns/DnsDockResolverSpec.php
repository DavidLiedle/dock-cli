<?php

namespace spec\Dock\Docker\Dns;

use Dock\Docker\Dns\ContainerAddressResolver;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DnsDockResolverSpec extends ObjectBehavior
{
    function it_is_a_container_address_resolve()
    {
        $this->shouldImplement(ContainerAddressResolver::class);
    }

    function it_returns_the_resolution_with_just_the_image_name()
    {
        $this->getDnsByContainerNameAndImage('container', 'image')->shouldContain('image.docker');
    }

    function it_returns_the_container_specific_resolution()
    {
        $this->getDnsByContainerNameAndImage('container', 'image')->shouldContain('container.image.docker');
    }
}

<?php

/*
 * This file is part of the Fidry\Console package.
 *
 * (c) Théo FIDRY <theo.fidry@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare (strict_types=1);
namespace BitPayVendor\Fidry\Console\Test;

use BitPayVendor\Fidry\Console\Command\Command;
use BitPayVendor\Fidry\Console\Command\SymfonyCommand;
use BitPayVendor\Fidry\Console\DisplayNormalizer;
use BitPayVendor\Symfony\Component\Console\Application;
use BitPayVendor\Symfony\Component\Console\Tester\CommandTester as SymfonyCommandTester;
use BitPayVendor\Webmozart\Assert\Assert;
/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CommandTester extends SymfonyCommandTester
{
    /**
     * If your command does not depend on any special behavior from the console, this static factory will probably be
     * good enough for you.
     * Otherwise, consider using the AppTester!
     */
    public static function fromConsoleCommand(Command $command) : self
    {
        // A bare-bone application is needed to execute the Symfony CommandTester as what it does is configuring the
        // application and using it to execute the command.
        $application = new Application();
        $executableCommand = $application->add(new SymfonyCommand($command));
        Assert::notNull($executableCommand);
        return new self($executableCommand);
    }
    public function getNormalizedDisplay(callable ...$extraNormalizers) : string
    {
        return DisplayNormalizer::removeTrailingSpaces($this->getDisplay(), ...$extraNormalizers);
    }
}

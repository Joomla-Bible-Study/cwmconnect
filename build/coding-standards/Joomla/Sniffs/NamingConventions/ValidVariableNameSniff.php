<?php
/**
 * Joomla! Coding Standard
 *
 * @package    Joomla.CodingStandard
 * @copyright  Copyright (C) 2015 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */
namespace Joomla\Sniffs\NamingConventions;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Common;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\NamingConventions\ValidVariableNameSniff as SquizValidvariableNameSniff;
/**
 * Extended ruleset for checking the naming of variables and member variables.
 *
 * @since     1.0
 */
class ValidVariableNameSniff extends SquizValidVariableNameSniff
{
	/**
	 * Processes class member variables.
	 *
	 * Extends Squiz.NamingConventions.ValidVariableName.processMemberVar to remove the requirement for leading underscores on
	 * private member vars.
	 *
	 * @param   PHP_CodeSniffer\Files\File  $phpcsFile  The file being scanned.
	 * @param   integer                     $stackPtr   The position of the current token in the stack passed in $tokens.
	 *
	 * @return  void
	 */
	protected function processMemberVar(File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();

		$varName     = ltrim($tokens[$stackPtr]['content'], '$');
		$memberProps = $phpcsFile->getMemberProperties($stackPtr);

		if (empty($memberProps) === true)
		{
			// Couldn't get any info about this variable, which generally means it is invalid or possibly has a parse
			// error. Any errors will be reported by the core, so we can ignore it.
			return;
		}

		$errorData = array($varName);

		if (substr($varName, 0, 1) === '_')
		{
			$error = '%s member variable "%s" must not contain a leading underscore';
			$data  = array(
				ucfirst($memberProps['scope']),
				$errorData[0]
			);
			$phpcsFile->addError($error, $stackPtr, 'ClassVarHasUnderscore', $data);

			return;
		}

		if (Common::isCamelCaps($varName, false, true, false) === false)
		{
			$error = 'Member variable "%s" is not in valid camel caps format';
			$phpcsFile->addError($error, $stackPtr, 'MemberNotCamelCaps', $errorData);
		}
	}
}

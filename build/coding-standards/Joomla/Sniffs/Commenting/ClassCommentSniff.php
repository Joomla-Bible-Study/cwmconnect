<?php
/**
 * Joomla! Coding Standard
 *
 * @package    Joomla.CodingStandard
 * @copyright  Copyright (C) 2015 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */
namespace Joomla\Sniffs\Commenting;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Joomla\Sniffs\Commenting\FileCommentSniff as JoomlaFileCommentSniff;
/**
 * Parses and verifies the doc comments for classes.
 *
 * @since     1.0
 */
class ClassCommentSniff extends JoomlaFileCommentSniff
{
	/**
	 * Tags in correct order and related info.
	 *
	 * @var  array
	 */
	protected $tags = array(
		'@category'   => array(
			'required'       => false,
			'allow_multiple' => false,
			'order_text'     => 'is first',
		),
		'@package'    => array(
			'required'       => false,
			'allow_multiple' => false,
			'order_text'     => 'must follow @category (if used)',
		),
		'@subpackage' => array(
			'required'       => false,
			'allow_multiple' => false,
			'order_text'     => 'must follow @package',
		),
		'@author'     => array(
			'required'       => false,
			'allow_multiple' => true,
			'order_text'     => 'is first',
		),
		'@copyright'  => array(
			'required'       => false,
			'allow_multiple' => true,
			'order_text'     => 'must follow @author (if used) or @subpackage (if used) or @package (if used)',
		),
		'@license'    => array(
			'required'       => false,
			'allow_multiple' => false,
			'order_text'     => 'must follow @copyright (if used)',
		),
		'@link'      => array(
			'required'       => false,
			'allow_multiple' => true,
			'order_text'     => 'must follow @license (if used)',
		),
		'@see'        => array(
			'required'       => false,
			'allow_multiple' => true,
			'order_text'     => 'must follow @link (if used)',
		),
		'@since'      => array(
			'required'       => true,
			'allow_multiple' => false,
			'order_text'     => 'must follow @see (if used) or @link (if used)',
		),
		'@deprecated' => array(
			'required'       => false,
			'allow_multiple' => false,
			'order_text'     => 'must follow @since (if used) or @see (if used) or @link (if used)',
		),
	);

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return  array
	 */
	public function register()
	{
		return array(
			T_CLASS,
			T_INTERFACE,
			T_TRAIT
		);
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param   PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
	 * @param   int                        $stackPtr  The position of the current token
	 *                                        in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(File $phpcsFile, $stackPtr)
	{
		$this->currentFile = $phpcsFile;
		$tokens	= $phpcsFile->getTokens();
		$type      = strtolower($tokens[$stackPtr]['content']);
		$errorData = array($type);
		$find   = Tokens::$methodPrefixes;
		$find[] = T_WHITESPACE;
		$commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);

		if ($tokens[$commentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG
			&& $tokens[$commentEnd]['code'] !== T_COMMENT
		)
		{
			$phpcsFile->addError('Missing class doc comment', $stackPtr, 'Missing');
			$phpcsFile->recordMetric($stackPtr, 'Class has doc comment', 'no');

			return;
		}

		$phpcsFile->recordMetric($stackPtr, 'Class has doc comment', 'yes');

		if ($tokens[$commentEnd]['code'] === T_COMMENT)
		{
			$phpcsFile->addError('You must use "/**" style comments for a class comment', $stackPtr, 'WrongStyle');

			return;
		}

		// Check each tag.
		$this->processTags($phpcsFile, $stackPtr, $tokens[$commentEnd]['comment_opener']);
	}//end process()
}

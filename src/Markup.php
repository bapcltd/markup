<?php
/**
* @author Marv Blackwell
*/
declare(strict_types=1);

namespace BAPC\Html;

use DOMElement;
use InvalidArgumentException;
use Masterminds\HTML5;
use SignpostMarv\DaftMarkup\Markup as Base;
use SignpostMarv\DaftMarkup\MarkupUtilities;
use SignpostMarv\DaftMarkup\MarkupValidator;

class Markup extends Base
{
	/**
	* @param array{!element:string, !attributes:array<string, scalar|array<int, scalar>>, !content?:array<int, scalar|array{!element:string}>} $markup
	*/
	public function MarkupArrayToMarkupString(
		array $markup,
		bool $xml_style = self::DEFAULT_BOOL_XML_STYLE,
		int $flags = self::DEFAULT_BITWISE_FLAGS,
		string $encoding = self::DEFAULT_STRING_ENCODING,
		bool $double = self::DEFAULT_BOOL_DOUBLE_ENCODE
	) : string {
		if ('esi:include' === $markup['!element']) {
			return
				'<esi:include' .
				MarkupUtilities::MarkupAttributesArrayToMarkupString(
					MarkupValidator::ValidateMarkupAttributes($markup),
					$flags,
					$encoding,
					$double
				) .
				' />';
		}

		return parent::MarkupArrayToMarkupString(
			$markup,
			$xml_style,
			$flags,
			$encoding,
			$double
		);
	}

	/**
	* @param array<string, string[]> $exclude_elements
	* @param array<string, string[]> $keep_elements
	* @param array<int, string> $general_attribute_whitelist
	*
	* @return array<int, array{!element:string, !attributes:array<string, scalar|array<int, scalar>>, !content?:array<int, scalar|array{!element:string}>}>
	*/
	public function UncertainMarkupStringToMarkupArray(
		string $markup,
		array $exclude_elements = [],
		array $keep_elements = [],
		array $general_attribute_whitelist = []
	) : array {
		return $this->MarkupStringToMarkupArray(
			$markup,
			array_merge(
				$exclude_elements,
				[
					'script' => [],
					'esi:include' => [],
					'object' => [],
					'embed' => [],
					'applet' => [],
				]
			),
			$keep_elements,
			$general_attribute_whitelist
		);
	}

	protected function MarkupArrayContentToMarkupString(
		string $element,
		array $content,
		bool $xml_style = self::DEFAULT_BOOL_XML_STYLE,
		int $flags = self::DEFAULT_BITWISE_FLAGS,
		string $encoding = self::DEFAULT_STRING_ENCODING,
		bool $double = self::DEFAULT_BOOL_DOUBLE_ENCODE
	) : string {
		if ('style' === $element || 'script' === $element) {
			$doc = new HTML5();

			$count = count($content);

			$content = array_filter($content, 'is_string');

			if (count($content) !== $count) {
				throw new InvalidArgumentException(
					'Cannot convert non-string content to <' . $element . '> content!'
				);
			}

			$frag = $doc->loadHTMLFragment('<' . $element . '>' . implode('', $content) . '</' . $element . '>');

			if (
				($frag->childNodes[0] instanceof DOMElement) &&
				$element === $frag->childNodes[0]->nodeName
			) {
				return '>' . $frag->childNodes[0]->textContent . '</' . $element . '>';
			}
			throw new InvalidArgumentException(
				'Cannot convert non-string content to <' . $element . '> content!'
			);
		}

		return parent::MarkupArrayContentToMarkupString(
			$element,
			$content,
			$xml_style,
			$flags,
			$encoding
		);
	}
}

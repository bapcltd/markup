<?php
/**
* @author Marv Blackwell
*/
declare(strict_types=1);

namespace BAPC\Html;

use Generator;
use InvalidArgumentException;
use SignpostMarv\DaftMarkup\Tests\ConverterTest as Base;
use Throwable;

class ConverterTest extends Base
{
	/**
	 * @return array<int, array{0:class-string<Markup>, 1:array<int, mixed>}>
	 */
	public function dataProviderMarkupFactory() : array
	{
		return [
			[
				Markup::class,
				[],
			],
		];
	}

	/**
	 * @return array<int, array<int, mixed|array<int|string, mixed>|string>>
	 */
	public function dataProviderMarkupArrayToMarkupString() : array
	{
		$out = parent::dataProviderMarkupArrayToMarkupString();

		$out[] = [
			'<style>.foo > .bar {baz: "bat";}</style>',
			[
				[
					'!element' => 'style',
					'!content' => [
						'.foo > .bar {baz: "bat";}',
					],
				],
			],
		];

		$out[] = [
			'<style>.foo > .bar {baz: "bat";}</style>',
			[
				[
					'!element' => 'style',
					'!content' => [
						'.foo > .bar {baz: "bat";}',
						'</style><script>/** bad stuff here */</script><style>',
					],
				],
			],
		];

		$out[] = [
			'<esi:include src="./foo" />',
			[
				[
					'!element' => 'esi:include',
					'!attributes' => [
						'src' => './foo',
					],
				],
			],
		];

		$out[] = [
			'<img intrinsicsize width="1" height="2">',
			[
				[
					'!element' => 'img',
					'!attributes' => [
						'intrinsicsize' => true,
						'width' => '1',
						'height' => '2',
					],
				],
			],
		];

		return $out;
	}

	public function dataProviderUncertainMarkupStringToMarkupArray() : array
	{
		return [
			[
				[
					'!element' => 'div',
					'!content' => [
						'foo',
						[
							'!element' => 'script',
							'!content' => [
								'/* hypothetical bad stuff here */',
							],
						],
						'bar',
					],
				],
				'<div>foobar</div>',
			],
			[
				[
					[
						'!element' => 'img',
						'!attributes' => [
							'intrinsicsize' => true,
							'width' => '1',
							'height' => '2',
						],
					],
				],
				'<img intrinsicsize width="1" height="2">',
			],
		];
	}

	/**
	 * @return Generator<int, array{0:class-string<Markup>, 1:mixed[], 2:array, 3:string, 4:array<string, string[]>, 5:array<string, string[]>, 6:array<int, string>}, mixed, void>
	 */
	public function dataProviderMarkupFactoryPlusUncertainMarkupStringToMarkupArray() : Generator
	{
		foreach ($this->dataProviderMarkupFactory() as $k => $markupArgs) {
			if (
				self::EXPECTED_MARKUP_FACTORY_ARGUMENTS !== count($markupArgs) ||
				! isset($markupArgs[0], $markupArgs[1])
			) {
				throw new BadMethodCallException(sprintf(
					'%s::dataProviderMarkupFactory() contains insufficient args at index %s',
					static::class,
					$k
				));
			} elseif ( ! is_string($markupArgs[0])) {
				throw new BadMethodCallException(sprintf(
					'%s::dataProviderMarkupFactory() contains an invalid class value at index %s',
					static::class,
					$k
				));
			} elseif ( ! is_array($markupArgs[1])) {
				throw new BadMethodCallException(sprintf(
					'%s::dataProviderMarkupFactory() contains an invalid constructor args at index %s',
					static::class,
					$k
				));
			}

			/**
			 * @var string
			 * @var mixed[] $ctorargs
			 */
			[$class, $ctorargs] = $markupArgs;

			foreach ($this->dataProviderMarkupStringToMarkupArray() as $v) {
				/**
				 * @var array{0:class-string<Markup>, 1:mixed[], 2:array, 3:string, 4:array<string, string[]>, 5:array<string, string[]>, 6:array<int, string>}
				 */
				$out = array_merge(
					[$class, $ctorargs],
					is_array($v) ? $v : [$v]
				);

				yield $out;
			}
		}
	}

	/**
	 * @param class-string<Markup> $class,
	 * @param array<string, string[]> $excludeElements
	 * @param array<string, string[]> $keepElements
	 * @param array<int, string> $generalAttrWhitelist
	 *
	 * @dataProvider dataProviderMarkupFactoryPlusUncertainMarkupStringToMarkupArray
	 */
	public function test_uncertain_markup_string_to_markup_array(
		string $class,
		array $ctorargs,
		array $expected,
		string $markup,
		array $excludeElements = [],
		array $keepElements = [],
		array $generalAttrWhitelist = []
	) : void {
		/**
		 * @var Markup
		 */
		$converter = 0 === count($ctorargs) ? new $class() : new $class(...$ctorargs);
		static::assertSame(
			$expected,
			$converter->UncertainMarkupStringToMarkupArray(
				$markup,
				$excludeElements,
				$keepElements,
				$generalAttrWhitelist
			)
		);
	}

	public function dataProviderMarkupArrayToMarkupStringFailure() : array
	{
		return [
			[
				InvalidArgumentException::class,
				'Cannot convert non-string content to <style> content!',
				[
					'!element' => 'style',
					'!content' => [
						[
							'!element' => 'br',
						],
					],
				],
			],
		];
	}

	/**
	 * @return Generator<
	 *	int,
	 *	array{
	 *		0:class-string<Markup>,
	 *		1:array<int, mixed>,
	 *		2:class-string<Throwable>,
	 *		3:string,
	 *		4:array{
	 *			!element:string,
	 *			!attributes:array<
	 *				string,
	 *				scalar|array<int, scalar>
	 *			>,
	 *			!content?:array<int, scalar|array{!element:string}>
	 *		},
	 *		5?:bool,
	 *		6?:int,
	 *		7?:string,
	 *		8?:bool
	 *	},
	 *	mixed,
	 *	void
	 * >
	 */
	public function dataProviderMarkupArrayToMarkupStringFailureWithMarkupInstanceArgs() : Generator
	{
		foreach ($this->dataProviderMarkupFactory() as $k => $markupArgs) {
			[$class, $ctorargs] = $markupArgs;

			foreach (
				$this->dataProviderMarkupArrayToMarkupStringFailure() as $v
			) {
				/**
				 * @var array{
				 *	0:class-string<Markup>,
				 *	1:array<int, mixed>,
				 *	2:class-string<Throwable>,
				 *	3:string,
				 *	4:array{
				 *		!element:string,
				 *		!attributes:array<
				 *			string,
				 *			scalar|array<int, scalar>
				 *		>,
				 *		!content?:array<int, scalar|array{!element:string}>
				 *	},
				 *	5?:bool,
				 *	6?:int,
				 *	7?:string,
				 *	8?:bool
				 * }
				 */
				$out = array_merge([$class, $ctorargs], $v);

				yield $out;
			}
		}
	}

	/**
	 * @dataProvider dataProviderMarkupArrayToMarkupStringFailureWithMarkupInstanceArgs
	 *
	 * @param class-string<Markup> $markup_class
	 * @param array<int, mixed> $markup_ctor_args
	 * @param class-string<Throwable> $expected_exception
	 * @param array{
	 *	!element:string,
	 *	!attributes:array<string, scalar|array<int, scalar>>,
	 *	!content?:array<int, scalar|array{!element:string}>
	 * } $markup
	 */
	public function test_markup_array_to_markup_string_failure(
		string $markup_class,
		array $markup_ctor_args,
		string $expected_exception,
		string $expected_exception_message,
		array $markup,
		bool $xml_style = Markup::DEFAULT_BOOL_XML_STYLE,
		int $flags = Markup::DEFAULT_BITWISE_FLAGS,
		string $encoding = Markup::DEFAULT_STRING_ENCODING,
		bool $double = Markup::DEFAULT_BOOL_DOUBLE_ENCODE
	) : void {
		$instance = new $markup_class(...$markup_ctor_args);

		$this->expectException($expected_exception);
		$this->expectExceptionMessage($expected_exception_message);

		$instance->MarkupArrayToMarkupString(
			$markup,
			$xml_style,
			$flags,
			$encoding,
			$double
		);
	}
}

<?php

namespace Inteve\FeedGenerator\Feeds\GoogleMerchant;

use Inteve\FeedGenerator\Feed;
use Inteve\FeedGenerator\InvalidItemException;
use Inteve\FeedGenerator\IOutput;
use Inteve\FeedGenerator\Utils\Helpers;


class GoogleMerchantFeed extends Feed
{
	/** @var string|NULL */
	private $title;

	/** @var string|NULL */
	private $websiteUrl;

	/** @var \DateTimeInterface|NULL */
	private $updated;

	/** @var string|NULL */
	private $author;


	/**
	 * @return string|NULL
	 */
	public function getTitle()
	{
		return $this->title;
	}


	/**
	 * @param string
	 * @return self
	 */
	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}


	/**
	 * @return string|NULL
	 */
	public function getWebsiteUrl()
	{
		return $this->websiteUrl;
	}


	/**
	 * @param string
	 * @return self
	 */
	public function setWebsiteUrl($websiteUrl)
	{
		$this->websiteUrl = $websiteUrl;
		return $this;
	}


	/**
	 * @return \DateTimeInterface|NULL
	 */
	public function getUpdated()
	{
		return $this->updated;
	}


	/**
	 * @param \DateTimeInterface
	 * @return self
	 */
	public function setUpdated(\DateTimeInterface $updated)
	{
		$this->updated = $updated;
		return $this;
	}


	/**
	 * @return string|NULL
	 */
	public function getAuthor()
	{
		return $this->author;
	}


	/**
	 * @param string
	 * @return self
	 */
	public function setAuthor($author)
	{
		$this->author = $author;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getContentType()
	{
		return 'application/atom+xml';
	}


	/**
	 * @return void
	 * @throws InvalidItemException
	 */
	public function generate(IOutput $output)
	{
		// https://support.google.com/merchants/answer/160593
		$this->validate();
		$output->open();
		$output->output('<?xml version="1.0" encoding="utf-8"?>');
		$output->output("\n");
		$output->output('<feed xmlns="http://www.w3.org/2005/Atom" xmlns:g="http://base.google.com/ns/1.0">');
		$output->output("\n");

		$feedUpdated = $this->getUpdated();

		Helpers::writeXml($output, array(
			'title' => $this->getTitle(),
			'link' => array(
				'attrs' => array(
					'href' => $this->getWebsiteUrl(),
					'rel' => 'alternate',
					'type' => 'text/html',
				),
			),
			'updated' => $feedUpdated ? $feedUpdated->format(\DateTime::ATOM) : null,
			'author' => array(
				'content' => array(
					'name' => $this->getAuthor(),
				),
			),
		));

		foreach ($this->items as $item) {
			// https://support.google.com/merchants/answer/7052112

			if (!($item instanceof GoogleMerchantItem)) {
				throw new InvalidItemException('Feed item must be instance of GoogleMerchantItem.');
			}

			$item->validate();

			$output->output("<entry>\n");

			Helpers::writeXml($output, array(
				'g:id' => $item->getId(),
				'g:title' => $item->getTitle(),
				'g:description' => $item->getDescription(),
				'g:link' => $item->getUrl(),
				'g:image_link' => $item->getImageUrl(),

				// price & availability
				'g:availability' => $item->getAvailability(),
				'g:price' => $item->getPrice(),

				// features
				'g:condition' => $item->getCondition(),
				'g:adult' => $item->isAdult() ? 'yes' : 'no',
				'g:color' => $item->getColor(),
				'g:gender' => $item->getGender(),
				'g:size' => $item->getSize(),

				// group ID
				'g:item_group_id' => $item->getGroupId(),

				'g:shipping_label' => $item->getShippingLabel(),

				// identifiers
				'g:identifier_exists' => !$item->getEan() ? 'no' : null
			));

			$output->output('<g:shipping><g:price>'.$item->getShipping().'</g:price></g:shipping>');

			if ($item->getGoogleCategory()) {
				Helpers::writeXml($output, array(
					'g:google_product_category' => $item->getGoogleCategory()
				));
			}
			if ($item->getProductType()) {
				Helpers::writeXml($output, array(
					'g:product_type' => $item->getProductType()
				));
			}
			if ($item->getBrand()) {
				Helpers::writeXml($output, array(
					'g:brand' => $item->getBrand()
				));
			}

			if ($item->getEan()) {
				Helpers::writeXml($output, array(
					'g:gtin' => $item->getEan()
				));
			}

			if ($item->getPriceSale()) {
				Helpers::writeXml($output, array(
					'g:sale_price' => $item->getPriceSale()
				));
			}


			$output->output("</entry>\n");
		}

		$output->output('</feed>');
		$output->output("\n");
		$output->close();
	}


	/**
	 * @return void
	 * @throws AssertException
	 */
	protected function validate()
	{
		Helpers::assert(isset($this->title), 'Missing title, call $feed->setTitle().');
		Helpers::assert(isset($this->websiteUrl), 'Missing website URL, call $feed->setWebsiteUrl().');
		Helpers::assert(isset($this->updated), 'Missing update date, call $feed->setUpdated().');
		Helpers::assert(isset($this->author), 'Missing author, call $feed->setAuthor().');
	}
}

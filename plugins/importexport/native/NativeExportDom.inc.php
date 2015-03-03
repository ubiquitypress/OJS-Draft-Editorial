<?php

/**
 * @file plugins/importexport/native/NativeExportDom.inc.php
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeExportDom
 * @ingroup plugins_importexport_native
 *
 * @brief Native import/export plugin DOM functions for export
 */

import('lib.pkp.classes.xml.XMLCustomWriter');

class NativeExportDom {
	function &generateIssueDom(&$doc, &$journal, &$issue) {
		$xxxxxx =& XMLCustomWriter::createElement($doc, 'issue');

		NativeExportDom::generatePubId($doc, $xxxxxx, $issue, $issue);

		XMLCustomWriter::setAttribute($xxxxxx, 'published', $issue->getPublished()?'true':'false');

		switch (
			(int) $issue->getShowVolume() .
			(int) $issue->getShowNumber() .
			(int) $issue->getShowYear() .
			(int) $issue->getShowTitle()
		) {
			case '1111': $idType = 'num_vol_year_title'; break;
			case '1110': $idType = 'num_vol_year'; break;
			case '1010': $idType = 'vol_year'; break;
			case '0111': $idType = 'num_year_title'; break;
			case '0010': $idType = 'year'; break;
			case '1000': $idType = 'vol'; break;
			case '0001': $idType = 'title'; break;
			default: $idType = null;
		}
		XMLCustomWriter::setAttribute($xxxxxx, 'identification', $idType, false);

		XMLCustomWriter::setAttribute($xxxxxx, 'current', $issue->getCurrent()?'true':'false');
		XMLCustomWriter::setAttribute($xxxxxx, 'public_id', $issue->getPubId('publisher-id'), false);

		if (is_array($issue->getTitle(null))) foreach ($issue->getTitle(null) as $locale => $title) {
			$titleNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'title', $title, false);
			if ($titleNode) XMLCustomWriter::setAttribute($titleNode, 'locale', $locale);
			unset($titleNode);
		}
		if (is_array($issue->getDescription(null))) foreach ($issue->getDescription(null) as $locale => $description) {
			$descriptionNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'description', $description, false);
			if ($descriptionNode) XMLCustomWriter::setAttribute($descriptionNode, 'locale', $locale);
			unset($descriptionNode);
		}
		XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'volume', $issue->getVolume(), false);
		XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'number', $issue->getNumber(), false);
		XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'year', $issue->getYear(), false);

		if (is_array($issue->getShowCoverPage(null))) foreach (array_keys($issue->getShowCoverPage(null)) as $locale) {
			if ($issue->getShowCoverPage($locale)) {
				$coverNode =& XMLCustomWriter::createElement($doc, 'cover');
				XMLCustomWriter::appendChild($xxxxxx, $coverNode);
				XMLCustomWriter::setAttribute($coverNode, 'locale', $locale);

				XMLCustomWriter::createChildWithText($doc, $coverNode, 'caption', $issue->getCoverPageDescription($locale), false);

				$coverFile = $issue->getFileName($locale);
				if ($coverFile != '') {
					$imageNode =& XMLCustomWriter::createElement($doc, 'image');
					XMLCustomWriter::appendChild($coverNode, $imageNode);
					import('classes.file.PublicFileManager');
					$publicFileManager = new PublicFileManager();
					$coverPagePath = $publicFileManager->getJournalFilesPath($journal->getId()) . '/';
					$coverPagePath .= $coverFile;
					$embedNode =& XMLCustomWriter::createChildWithText($doc, $imageNode, 'embed', base64_encode($publicFileManager->readFile($coverPagePath)));
					XMLCustomWriter::setAttribute($embedNode, 'filename', $issue->getOriginalFileName($locale));
					XMLCustomWriter::setAttribute($embedNode, 'encoding', 'base64');
					XMLCustomWriter::setAttribute($embedNode, 'mime_type', String::mime_content_type($coverPagePath));
				}

				unset($coverNode);
			}
		}

		XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'date_published', NativeExportDom::formatDate($issue->getDatePublished()), false);

		if (XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'access_date', NativeExportDom::formatDate($issue->getOpenAccessDate()), false)==null) {
			// This may be an open access issue. Check and flag
			// as necessary.

			if ( // Issue flagged as open, or subscriptions disabled
				$issue->getAccessStatus() == ISSUE_ACCESS_OPEN ||
				$journal->getSetting('publishingMode') == PUBLISHING_MODE_OPEN
			) {
				$accessNode =& XMLCustomWriter::createElement($doc, 'open_access');
				XMLCustomWriter::appendChild($xxxxxx, $accessNode);
			}
		}

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		foreach ($sectionDao->getSectionsForIssue($issue->getId()) as $section) {
			$sectionNode =& NativeExportDom::generateSectionDom($doc, $journal, $issue, $section);
			XMLCustomWriter::appendChild($xxxxxx, $sectionNode);
			unset($sectionNode);
		}

		return $xxxxxx;
	}

	function &generateSectionDom(&$doc, &$journal, &$issue, &$section) {
		$xxxxxx =& XMLCustomWriter::createElement($doc, 'section');

		if (is_array($section->getTitle(null))) foreach ($section->getTitle(null) as $locale => $title) {
			$titleNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'title', $title, false);
			if ($titleNode) XMLCustomWriter::setAttribute($titleNode, 'locale', $locale);
			unset($titleNode);
		}

		if (is_array($section->getAbbrev(null))) foreach ($section->getAbbrev(null) as $locale => $abbrev) {
			$abbrevNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'abbrev', $abbrev, false);
			if ($abbrevNode) XMLCustomWriter::setAttribute($abbrevNode, 'locale', $locale);
			unset($abbrevNode);
		}

		if (is_array($section->getIdentifyType(null))) foreach ($section->getIdentifyType(null) as $locale => $identifyType) {
			$identifyTypeNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'identify_type', $identifyType, false);
			if ($identifyTypeNode) XMLCustomWriter::setAttribute($identifyTypeNode, 'locale', $locale);
			unset($identifyTypeNode);
		}

		if (is_array($section->getPolicy(null))) foreach ($section->getPolicy(null) as $locale => $policy) {
			$policyNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'policy', $policy, false);
			if ($policyNode) XMLCustomWriter::setAttribute($policyNode, 'locale', $locale);
			unset($policyNode);
		}

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		foreach ($publishedArticleDao->getPublishedArticlesBySectionId($section->getId(), $issue->getId()) as $article) {
			$articleNode =& NativeExportDom::generateArticleDom($doc, $journal, $issue, $section, $article);
			XMLCustomWriter::appendChild($xxxxxx, $articleNode);
			unset($articleNode);
		}

		return $xxxxxx;
	}

	function &generateArticleDom(&$doc, &$journal, &$issue, &$section, &$article) {
		$xxxxxx =& XMLCustomWriter::createElement($doc, 'article');
		XMLCustomWriter::setAttribute($xxxxxx, 'locale', $article->getLocale());
		XMLCustomWriter::setAttribute($xxxxxx, 'public_id', $article->getPubId('publisher-id'), false);
		XMLCustomWriter::setAttribute($xxxxxx, 'language', $article->getLanguage(), false);

		NativeExportDom::generatePubId($doc, $xxxxxx, $article, $issue);

		/* --- Titles and Abstracts --- */
		if (is_array($article->getTitle(null))) foreach ($article->getTitle(null) as $locale => $title) {
			$titleNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'title', $title, false);
			if ($titleNode) XMLCustomWriter::setAttribute($titleNode, 'locale', $locale);
			unset($titleNode);
		}

		if (is_array($article->getAbstract(null))) foreach ($article->getAbstract(null) as $locale => $abstract) {
			$abstractNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'abstract', $abstract, false);
			if ($abstractNode) XMLCustomWriter::setAttribute($abstractNode, 'locale', $locale);
			unset($abstractNode);
		}

		/* --- Indexing --- */

		$indexingNode =& XMLCustomWriter::createElement($doc, 'indexing');
		$isIndexingNecessary = false;

		if (is_array($article->getDiscipline(null))) foreach ($article->getDiscipline(null) as $locale => $discipline) {
			$disciplineNode =& XMLCustomWriter::createChildWithText($doc, $indexingNode, 'discipline', $discipline, false);
			if ($disciplineNode) {
				XMLCustomWriter::setAttribute($disciplineNode, 'locale', $locale);
				$isIndexingNecessary = true;
			}
			unset($disciplineNode);
		}
		if (is_array($article->getType(null))) foreach ($article->getType(null) as $locale => $type) {
			$typeNode =& XMLCustomWriter::createChildWithText($doc, $indexingNode, 'type', $type, false);
			if ($typeNode) {
				XMLCustomWriter::setAttribute($typeNode, 'locale', $locale);
				$isIndexingNecessary = true;
			}
			unset($typeNode);
		}
		if (is_array($article->getSubject(null))) foreach ($article->getSubject(null) as $locale => $subject) {
			$subjectNode =& XMLCustomWriter::createChildWithText($doc, $indexingNode, 'subject', $subject, false);
			if ($subjectNode) {
				XMLCustomWriter::setAttribute($subjectNode, 'locale', $locale);
				$isIndexingNecessary = true;
			}
			unset($subjectNode);
		}
		if (is_array($article->getSubjectClass(null))) foreach ($article->getSubjectClass(null) as $locale => $subjectClass) {
			$subjectClassNode =& XMLCustomWriter::createChildWithText($doc, $indexingNode, 'subject_class', $subjectClass, false);
			if ($subjectClassNode) {
				XMLCustomWriter::setAttribute($subjectClassNode, 'locale', $locale);
				$isIndexingNecessary = true;
			}
			unset($subjectClassNode);
		}

		$coverageNode =& XMLCustomWriter::createElement($doc, 'coverage');
		$isCoverageNecessary = false;

		if (is_array($article->getCoverageGeo(null))) foreach ($article->getCoverageGeo(null) as $locale => $geographical) {
			$geographicalNode =& XMLCustomWriter::createChildWithText($doc, $coverageNode, 'geographical', $geographical, false);
			if ($geographicalNode) {
				XMLCustomWriter::setAttribute($geographicalNode, 'locale', $locale);
				$isCoverageNecessary = true;
			}
			unset($geographicalNode);
		}
		if (is_array($article->getCoverageChron(null))) foreach ($article->getCoverageChron(null) as $locale => $chronological) {
			$chronologicalNode =& XMLCustomWriter::createChildWithText($doc, $coverageNode, 'chronological', $chronological, false);
			if ($chronologicalNode) {
				XMLCustomWriter::setAttribute($chronologicalNode, 'locale', $locale);
				$isCoverageNecessary = true;
			}
			unset($chronologicalNode);
		}
		if (is_array($article->getCoverageSample(null))) foreach ($article->getCoverageSample(null) as $locale => $sample) {
			$sampleNode =& XMLCustomWriter::createChildWithText($doc, $coverageNode, 'sample', $sample, false);
			if ($sampleNode) {
				XMLCustomWriter::setAttribute($sampleNode, 'locale', $locale);
				$isCoverageNecessary = true;
			}
			unset($sampleNode);
		}

		if ($isCoverageNecessary) {
			XMLCustomWriter::appendChild($indexingNode, $coverageNode);
			$isIndexingNecessary = true;
		}

		if ($isIndexingNecessary) XMLCustomWriter::appendChild($xxxxxx, $indexingNode);

		/* --- */

		/* --- Authors --- */

		foreach ($article->getAuthors() as $author) {
			$authorNode =& NativeExportDom::generateAuthorDom($doc, $journal, $issue, $article, $author);
			XMLCustomWriter::appendChild($xxxxxx, $authorNode);
			unset($authorNode);
		}

		/* --- */
		if (is_array($article->getShowCoverPage(null))) foreach (array_keys($article->getShowCoverPage(null)) as $locale) {
			if ($article->getShowCoverPage($locale)) {
				$coverNode =& XMLCustomWriter::createElement($doc, 'cover');
				XMLCustomWriter::appendChild($xxxxxx, $coverNode);
				XMLCustomWriter::setAttribute($coverNode, 'locale', $locale);

				XMLCustomWriter::createChildWithText($doc, $coverNode, 'altText', $issue->getCoverPageDescription($locale), false);

				$coverFile = $article->getFileName($locale);
				if ($coverFile != '') {
					$imageNode =& XMLCustomWriter::createElement($doc, 'image');
					XMLCustomWriter::appendChild($coverNode, $imageNode);
					import('classes.file.PublicFileManager');
					$publicFileManager = new PublicFileManager();
					$coverPagePath = $publicFileManager->getJournalFilesPath($journal->getId()) . '/';
					$coverPagePath .= $coverFile;
					$embedNode =& XMLCustomWriter::createChildWithText($doc, $imageNode, 'embed', base64_encode($publicFileManager->readFile($coverPagePath)));
					XMLCustomWriter::setAttribute($embedNode, 'filename', $article->getOriginalFileName($locale));
					XMLCustomWriter::setAttribute($embedNode, 'encoding', 'base64');
					XMLCustomWriter::setAttribute($embedNode, 'mime_type', String::mime_content_type($coverPagePath));
				}

				unset($coverNode);
			}
		}

		XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'pages', $article->getPages(), false);

		// NOTE that this is a required field for import, but it's
		// possible here to generate nonconforming XML via export b/c
		// of the potentially missing date_published node. This is due
		// to legacy data issues WRT an earlier lack of ability to
		// define article pub dates. Some legacy data will be missing
		// this date.
		XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'date_published', NativeExportDom::formatDate($article->getDatePublished()), false);

		if ($article->getAccessStatus() == ARTICLE_ACCESS_OPEN) {
			$accessNode =& XMLCustomWriter::createElement($doc, 'open_access');
			XMLCustomWriter::appendChild($xxxxxx, $accessNode);
		}

		/* --- */


		/* --- Galleys --- */
		foreach ($article->getGalleys() as $galley) {
			$galleyNode =& NativeExportDom::generateGalleyDom($doc, $journal, $issue, $article, $galley);
			if ($galleyNode !== null) XMLCustomWriter::appendChild($xxxxxx, $galleyNode);
			unset($galleyNode);

		}

		/* --- Supplementary Files --- */
		foreach ($article->getSuppFiles() as $suppFile) {
			$suppNode =& NativeExportDom::generateSuppFileDom($doc, $journal, $issue, $article, $suppFile);
			if ($suppNode !== null) XMLCustomWriter::appendChild($xxxxxx, $suppNode);
			unset($suppNode);
		}

		return $xxxxxx;
	}

	function &generateAuthorDom(&$doc, &$journal, &$issue, &$article, &$author) {
		$xxxxxx =& XMLCustomWriter::createElement($doc, 'author');
		if ($author->getPrimaryContact()) XMLCustomWriter::setAttribute($xxxxxx, 'primary_contact', 'true');

		XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'firstname', $author->getFirstName());
		XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'middlename', $author->getMiddleName(), false);
		XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'lastname', $author->getLastName());

		$affiliations = $author->getAffiliation(null);
		if (is_array($affiliations)) foreach ($affiliations as $locale => $affiliation) {
			$affiliationNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'affiliation', $affiliation, false);
			if ($affiliationNode) XMLCustomWriter::setAttribute($affiliationNode, 'locale', $locale);
			unset($affiliationNode);
		}
		XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'country', $author->getCountry(), false);
		XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'email', $author->getEmail());
		XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'url', $author->getUrl(), false);
		if (is_array($author->getCompetingInterests(null))) foreach ($author->getCompetingInterests(null) as $locale => $competingInterests) {
			$competingInterestsNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'competing_interests', $competingInterests, false);
			if ($competingInterestsNode) XMLCustomWriter::setAttribute($competingInterestsNode, 'locale', $locale);
			unset($competingInterestsNode);
		}
		if (is_array($author->getBiography(null))) foreach ($author->getBiography(null) as $locale => $biography) {
			$biographyNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'biography', $biography, false);
			if ($biographyNode) XMLCustomWriter::setAttribute($biographyNode, 'locale', $locale);
			unset($biographyNode);
		}

		return $xxxxxx;
	}

	function &generateGalleyDom(&$doc, &$journal, &$issue, &$article, &$galley) {
		$isHtml = $galley->isHTMLGalley();

		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($article->getId());
		$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');

		$xxxxxx =& XMLCustomWriter::createElement($doc, $isHtml?'htmlgalley':'galley');
		XMLCustomWriter::setAttribute($xxxxxx, 'locale', $galley->getLocale());
		XMLCustomWriter::setAttribute($xxxxxx, 'public_id', $galley->getPubId('publisher-id'), false);

		NativeExportDom::generatePubId($doc, $xxxxxx, $galley, $issue);

		XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'label', $galley->getLabel());

		/* --- Galley file --- */
		$fileNode =& XMLCustomWriter::createElement($doc, 'file');
		XMLCustomWriter::appendChild($xxxxxx, $fileNode);
		if ($galley->getRemoteURL()) {
			$remoteNode =& XMLCustomWriter::createElement($doc, 'remote');
			XMLCustomWriter::appendChild($fileNode, $remoteNode);
			XMLCustomWriter::setAttribute($remoteNode, 'src', $galley->getRemoteURL());
		} else {
			$embedNode =& XMLCustomWriter::createChildWithText($doc, $fileNode, 'embed', base64_encode($articleFileManager->readFile($galley->getFileId())));
			$articleFile =& $articleFileDao->getArticleFile($galley->getFileId());
			if (!$articleFile) return $articleFile; // Stupidity check
			XMLCustomWriter::setAttribute($embedNode, 'filename', $articleFile->getOriginalFileName());
			XMLCustomWriter::setAttribute($embedNode, 'encoding', 'base64');
			XMLCustomWriter::setAttribute($embedNode, 'mime_type', $articleFile->getFileType());

			/* --- HTML-specific data: Stylesheet and/or images --- */

			if ($isHtml) {
				$styleFile = $galley->getStyleFile();
				if ($styleFile) {
					$styleNode =& XMLCustomWriter::createElement($doc, 'stylesheet');
					XMLCustomWriter::appendChild($xxxxxx, $styleNode);
					$embedNode =& XMLCustomWriter::createChildWithText($doc, $styleNode, 'embed', base64_encode($articleFileManager->readFile($styleFile->getFileId())));
					XMLCustomWriter::setAttribute($embedNode, 'filename', $styleFile->getOriginalFileName());
					XMLCustomWriter::setAttribute($embedNode, 'encoding', 'base64');
					XMLCustomWriter::setAttribute($embedNode, 'mime_type', 'text/css');
				}

				foreach ($galley->getImageFiles() as $imageFile) {
					$imageNode =& XMLCustomWriter::createElement($doc, 'image');
					XMLCustomWriter::appendChild($xxxxxx, $imageNode);
					$embedNode =& XMLCustomWriter::createChildWithText($doc, $imageNode, 'embed', base64_encode($articleFileManager->readFile($imageFile->getFileId())));
					XMLCustomWriter::setAttribute($embedNode, 'filename', $imageFile->getOriginalFileName());
					XMLCustomWriter::setAttribute($embedNode, 'encoding', 'base64');
					XMLCustomWriter::setAttribute($embedNode, 'mime_type', $imageFile->getFileType());
					unset($imageNode);
					unset($embedNode);
				}
			}
		}

		return $xxxxxx;
	}

	function &generateSuppFileDom(&$doc, &$journal, &$issue, &$article, &$suppFile) {
		$xxxxxx =& XMLCustomWriter::createElement($doc, 'supplemental_file');

		NativeExportDom::generatePubId($doc, $xxxxxx, $suppFile, $issue);

		// FIXME: These should be constants!
		switch ($suppFile->getType()) {
			case __('author.submit.suppFile.researchInstrument'):
				$suppFileType = 'research_instrument';
				break;
			case __('author.submit.suppFile.researchMaterials'):
				$suppFileType = 'research_materials';
				break;
			case __('author.submit.suppFile.researchResults'):
				$suppFileType = 'research_results';
				break;
			case __('author.submit.suppFile.transcripts'):
				$suppFileType = 'transcripts';
				break;
			case __('author.submit.suppFile.dataAnalysis'):
				$suppFileType = 'data_analysis';
				break;
			case __('author.submit.suppFile.dataSet'):
				$suppFileType = 'data_set';
				break;
			case __('author.submit.suppFile.sourceText'):
				$suppFileType = 'source_text';
				break;
			default:
				$suppFileType = 'other';
				break;
		}

		XMLCustomWriter::setAttribute($xxxxxx, 'type', $suppFileType);
		XMLCustomWriter::setAttribute($xxxxxx, 'public_id', $suppFile->getPubId('publisher-id'), false);
		XMLCustomWriter::setAttribute($xxxxxx, 'language', $suppFile->getLanguage(), false);
		XMLCustomWriter::setAttribute($xxxxxx, 'show_reviewers', $suppFile->getShowReviewers()?'true':'false');

		if (is_array($suppFile->getTitle(null))) foreach ($suppFile->getTitle(null) as $locale => $title) {
			$titleNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'title', $title, false);
			if ($titleNode) XMLCustomWriter::setAttribute($titleNode, 'locale', $locale);
			unset($titleNode);
		}
		if (is_array($suppFile->getCreator(null))) foreach ($suppFile->getCreator(null) as $locale => $creator) {
			$creatorNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'creator', $creator, false);
			if ($creatorNode) XMLCustomWriter::setAttribute($creatorNode, 'locale', $locale);
			unset($creatorNode);
		}
		if (is_array($suppFile->getSubject(null))) foreach ($suppFile->getSubject(null) as $locale => $subject) {
			$subjectNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'subject', $subject, false);
			if ($subjectNode) XMLCustomWriter::setAttribute($subjectNode, 'locale', $locale);
			unset($subjectNode);
		}
		if ($suppFileType == 'other') {
			if (is_array($suppFile->getTypeOther(null))) foreach ($suppFile->getTypeOther(null) as $locale => $typeOther) {
				$typeOtherNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'type_other', $typeOther, false);
				if ($typeOtherNode) XMLCustomWriter::setAttribute($typeOtherNode, 'locale', $locale);
				unset($typeOtherNode);
			}
		}
		if (is_array($suppFile->getDescription(null))) foreach ($suppFile->getDescription(null) as $locale => $description) {
			$descriptionNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'description', $description, false);
			if ($descriptionNode) XMLCustomWriter::setAttribute($descriptionNode, 'locale', $locale);
			unset($descriptionNode);
		}
		if (is_array($suppFile->getPublisher(null))) foreach ($suppFile->getPublisher(null) as $locale => $publisher) {
			$publisherNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'publisher', $publisher, false);
			if ($publisherNode) XMLCustomWriter::setAttribute($publisherNode, 'locale', $locale);
			unset($publisherNode);
		}
		if (is_array($suppFile->getSponsor(null))) foreach ($suppFile->getSponsor(null) as $locale => $sponsor) {
			$sponsorNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'sponsor', $sponsor, false);
			if ($sponsorNode) XMLCustomWriter::setAttribute($sponsorNode, 'locale', $locale);
			unset($sponsorNode);
		}
		XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'date_created', NativeExportDom::formatDate($suppFile->getDateCreated()), false);
		if (is_array($suppFile->getSource(null))) foreach ($suppFile->getSource(null) as $locale => $source) {
			$sourceNode =& XMLCustomWriter::createChildWithText($doc, $xxxxxx, 'source', $source, false);
			if ($sourceNode) XMLCustomWriter::setAttribute($sourceNode, 'locale', $locale);
			unset($sourceNode);
		}

		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($article->getId());
		$fileNode =& XMLCustomWriter::createElement($doc, 'file');
		XMLCustomWriter::appendChild($xxxxxx, $fileNode);
		if ($suppFile->getRemoteURL()) {
			$remoteNode =& XMLCustomWriter::createElement($doc, 'remote');
			XMLCustomWriter::appendChild($fileNode, $remoteNode);
			XMLCustomWriter::setAttribute($remoteNode, 'src', $suppFile->getRemoteURL());
		} else {
			$embedNode =& XMLCustomWriter::createChildWithText($doc, $fileNode, 'embed', base64_encode($articleFileManager->readFile($suppFile->getFileId())));
			XMLCustomWriter::setAttribute($embedNode, 'filename', $suppFile->getOriginalFileName());
			XMLCustomWriter::setAttribute($embedNode, 'encoding', 'base64');
			XMLCustomWriter::setAttribute($embedNode, 'mime_type', $suppFile->getFileType());
		}
		return $xxxxxx;
	}

	function formatDate($date) {
		if ($date == '') return null;
		return date('Y-m-d', strtotime($date));
	}

	/**
	 * Add ID-nodes to the given node.
	 * @param $doc DOMDocument
	 * @param $node DOMNode
	 * @param $pubObject object
	 * @param $issue Issue
	 */
	function generatePubId(&$doc, &$node, &$pubObject, &$issue) {
		$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $issue->getJournalId());
		if (is_array($pubIdPlugins)) foreach ($pubIdPlugins as $pubIdPlugin) {
			if ($issue->getPublished()) {
				$pubId = $pubIdPlugin->getPubId($pubObject);
			} else {
				$pubId = $pubIdPlugin->getPubId($pubObject, true);
			}
			if ($pubId) {
				$pubIdType = $pubIdPlugin->getPubIdType();
				$idNode =& XMLCustomWriter::createChildWithText($doc, $node, 'id', $pubId);
				XMLCustomWriter::setAttribute($idNode, 'type', $pubIdType);
			}
		}
	}
}

?>

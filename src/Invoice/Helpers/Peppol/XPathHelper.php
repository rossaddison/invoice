<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use DOMElement;
use DOMNode;

/**
 * Builds a stable XPath location string for a DOM node.
 *
 * Shared by PeppolValidator, AbstractCalculator, and AbstractRule so the
 * path-building logic lives in exactly one place.
 */
final class XPathHelper
{
    /**
     * What?  A readable XPath that locates a specific node for use in error messages,
     *        e.g. /cac:InvoiceLine[cbc:ID='3']/cbc:LineExtensionAmount[1].
     * Why?   Positional-only paths (e.g. [3]) drift if the DOM is reordered or nodes are
     *        imported from another document. Anchoring on cbc:ID where available keeps the
     *        path stable across those operations.
     * When?  Called whenever an error or violation is recorded that should carry an XPath
     *        location (addError in PeppolValidator, violation() in AbstractRule, addError
     *        in AbstractCalculator).
     * Where? Used by PeppolValidator::getNodeXPath(), AbstractCalculator::buildXPath(),
     *        and AbstractRule::violation().
     * How?   Walks up the DOM tree. For each element, tries idPredicate() first; falls back
     *        to counting preceding siblings if no cbc:ID child exists.
     */
    public static function buildPath(DOMNode $node): string
    {
        $path    = '';
        $current = $node;

        while ($current !== null && $current->nodeType === XML_ELEMENT_NODE) {
            $nodeName  = $current->nodeName;
            $predicate = ($current instanceof DOMElement)
                ? self::idPredicate($current)
                : null;

            if ($predicate === null) {
                $position = 1;
                $sibling  = $current->previousSibling;
                while ($sibling !== null) {
                    if ($sibling->nodeType === XML_ELEMENT_NODE
                        && $sibling->nodeName === $nodeName
                    ) {
                        $position++;
                    }
                    $sibling = $sibling->previousSibling;
                }
                $predicate = "[{$position}]";
            }

            $path    = "/{$nodeName}{$predicate}" . $path;
            $current = $current->parentNode;
        }

        return $path ?: '/';
    }

    /**
     * Return an XPath predicate based on the element's cbc:ID child, or null if absent.
     *
     * Quote style is chosen to keep the predicate syntactically valid even when the ID
     * value contains a quote character. Returns null if the value contains both quote
     * types (exceedingly unlikely for a UBL ID), allowing the caller to fall back to a
     * positional predicate.
     */
    private static function idPredicate(DOMElement $element): ?string
    {
        foreach ($element->childNodes as $child) {
            if (!($child instanceof DOMElement)
                || $child->localName !== 'ID'
                || $child->namespaceURI !== 'urn:oasis:names:specification:ubl:'
                    . 'schema:xsd:CommonBasicComponents-2'
            ) {
                continue;
            }

            $val = trim($child->nodeValue ?? '');
            if ($val === '') {
                return null;
            }

            if (!str_contains($val, "'")) {
                return "[cbc:ID='{$val}']";
            }
            if (!str_contains($val, '"')) {
                return "[cbc:ID=\"{$val}\"]";
            }

            return null; // Both quote types present — fall back to positional.
        }

        return null;
    }
}

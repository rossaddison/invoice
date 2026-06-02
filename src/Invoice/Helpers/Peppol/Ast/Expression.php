<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * Marker interface for every node in the Peppol / EN16931 expression AST.
 *
 * Each concrete node represents one XPath 2.0 construct used in the official
 * BIS Billing 3.0 Schematron files.  Nodes are immutable readonly value objects;
 * evaluation, compilation, or pretty-printing is handled by a separate visitor.
 *
 * Naming note
 * -----------
 * PHP reserves `and`, `or`, and `string` as class names (case-insensitive), so
 * those three nodes are called AndNode, OrNode, and StringCast respectively.
 * Every other node matches the name listed in the Schematron analysis report.
 */
interface Expression {}

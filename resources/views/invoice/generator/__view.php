<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
?>

<h1><?= Html::encode($title) ?></h1>

<div class="form-group">
    <div class="row mb-3 form-group">
        <label for="route_suffix" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Route Suffix</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['route_suffix'] ?? '') ?></label>
    </div>
    <div class="row mb-3 form-group">
        <label for="route_prefix" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Route Prefix</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['route_prefix'] ?? '') ?></label>
    </div>
    <div class="row mb-3 form-group">
        <label for="camelcase_capital_name" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Camelcase capital name</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['camelcase_capital_name'] ?? '') ?></label>
    </div>

    <div class="row mb-3 form-group">
        <label for="small_singular_name" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Small Singular Name</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['small_singular_name'] ?? '') ?></label>
    </div>
    <div class="row mb-3 form-group">
        <label for="small_plural_name" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Small Plural Name</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['small_plural_name'] ?? '') ?></label>
    </div>
    <div class="row mb-3 form-group">
        <label for="namespace_path" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Namespace Path</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['namespace_path'] ?? '') ?></label>
    </div>
    <div class="row mb-3 form-group">
        <label for="controller_layout_dir" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Controller Layout Dir</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['controller_layout_dir'] ?? '') ?></label>
    </div>
    <div class="row mb-3 form-group">
        <label for="controller_layout_dir_dot_path" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Controller Layout Directory Dot Path</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['controller_layout_dir_dot_path'] ?? '') ?></label>
    </div>
    <div class="row mb-3 form-group">
        <label for="repo_extra_camelcase_name" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Repo extra camelcase name</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['repo_extra_camelcase_name'] ?? '') ?></label>
    </div>


    <div class="row mb-3 form-group">
        <label for="paginator_next_page_attribute" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Paginator Next Page Attribute</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['paginator_next_page_attribute'] ?? '') ?></label>
    </div>
    <div class="row mb-3 form-group">
        <label for="pre_entity_table" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Pre Entity Table</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['pre_entity_table'] ?? '') ?></label>
    </div>
    <div class="row mb-3 form-group">
        <label for="constrain_index_field" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Index field used in a scope</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['constrain_index_field'] ?? '') ?></label>
    </div>

    <div class="row mb-3">
        <label for="created_include" class="text-bg col-sm-2 col-form-label">Include Date Created Field in Mapper</label>
        <input type="hidden" name="created_include" value="0">
        <input type="checkbox" name="created_include" id="created_include" value="1"
               <?php $s->check_select(Html::encode($body['created_include'] ?? ''), 1, '==', true) ?></label>
        disabled="true">
    </div>
    <div class="row mb-3">
        <label for="updated_include" class="text-bg col-sm-2 col-form-label">Include Date Updated Field in Mapper</label>
        <input type="hidden" name="updated_include" value="0">
        <input type="checkbox" name="updated_include" id="updated_include" value="1"
               <?php $s->check_select(Html::encode($body['updated_include'] ?? ''), 1, '==', true) ?></label>
        disabled="true">
    </div>
    <div class="row mb-3">
        <label for="modified_include" class="text-bg col-sm-2 col-form-label">Include Date Modified Field in Mapper</label>
        <input type="hidden" name="modified_include" value="0">
        <input type="checkbox" name="modified_include" id="modified_include" value="1"
               <?php $s->check_select(Html::encode($body['modified_include'] ?? ''), 1, '==', true) ?></label>
        disabled="true">
    </div>
    <div class="row mb-3">
        <label for="deleted_include" class="text-bg col-sm-2 col-form-label">Include Date Deleted Field in Mapper</label>
        <input type="hidden" name="deleted_include" value="0">
        <input type="checkbox" name="deleted_include" id="deleted_include" value="1"
               <?php $s->check_select(Html::encode($body['deleted_include'] ?? ''), 1, '==', true) ?></label>
        disabled="true">
    </div>
    <div class="row mb-3">
        <label for="keyset_paginator_include" class="text-bg col-sm-2 col-form-label">Include Keyset Paginator</label>
        <input type="hidden" name="keyset_paginator_include" value="0">
        <input type="checkbox" name="keyset_paginator_include" id="paginator_include" value="1"
               <?php $s->check_select(Html::encode($body['keyset_paginator_include'] ?? ''), 1, '==', true) ?></label>
        disabled="true">
    </div>
    <div class="row mb-3">
        <label for="offset_paginator_include" class="text-bg col-sm-2 col-form-label">Include Offset Paginator</label>
        <input type="hidden" name="offset_paginator_include" value="0">
        <input type="checkbox" name="offset_paginator_include" id="offset_paginator_include" value="1"
               <?php $s->check_select(Html::encode($body['offset_paginator_include'] ?? ''), 1, '==', true) ?></label>
        disabled="true">
    </div>
    <div class="row mb-3 form-group">
        <label for="filter_field" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Filter Field</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['filter_field'] ?? '') ?></label>
    </div>
    <div class="row mb-3 form-group">
        <label for="filter_field_start_position" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Filter Field Start Position</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['filter_field_start_position'] ?? '') ?></label>
    </div>
    <div class="row mb-3 form-group">
        <label for="filter_field_end_position" class="text-bg col-sm-2 col-form-label" style="background:lightblue">Filter Field End Position</label>
        <label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['filter_field_end_position'] ?? '') ?></label>
    </div>
    <div class="row mb-3">
        <label for="flash_include" class="text-bg col-sm-2 col-form-label">Include Flash Message</label>
        <input type="hidden" name="flash_include" value="0">
        <input type="checkbox"  name="flash_include" id="flash_include" value="1"
               <?php $s->check_select(Html::encode($body['flash_include'] ?? ''), 1, '==', true) ?></label>
        disabled="true">
    </div>

    <div class="row mb-3">
        <label for="headerline_include" class="text-bg col-sm-2 col-form-label">Include Headline if Ajax required</label>
        <input type="hidden" name="headerline_include" value="0">
        <input type="checkbox" name="headerline_include" id="headerline_include" value="1"
               <?php $s->check_select(Html::encode($body['headerline_include'] ?? ''), 1, '==', true) ?></label>
        disabled="true" >
    </div>
</div>


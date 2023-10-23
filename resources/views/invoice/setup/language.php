<?php
    declare(strict_types=1); 
?>
<div class="container">
    <div class="install-panel">

        <h1 id="logo"><span><?= $s->get_setting(''); ?></span></h1>

        <form method="post" action="<?= $urlGenerator->generate($redirect_string_index); ?>">

            <input type="hidden" name="_csrf" value="<?= $csrf ?>">

            <legend><?= $s->trans('setup_choose_language'); ?></legend>

            <p><?= $s->trans('setup_choose_language_message'); ?></p>

            <select name="lang" class="form-control simple-select">
                <?php foreach ($languages as $language) { ?>
                    <option value="<?= $language; ?>"
                            <?php if ($language == 'english') { ?>selected="selected"<?php } ?>>
                        <?= ucfirst(str_replace('/', '', $language)); ?>
                    </option>
                <?php } ?>
            </select>

            <br/>

            <input class="btn btn-success" type="submit" name="btn_continue" value="<?= $s->trans('continue'); ?>">

        </form>

    </div>
</div>

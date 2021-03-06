<?php
/**
 * Render the selector for surveys massive actions.
 *
 */
?>
<?php
    $model = SurveymenuEntries::model();
?>

<form class="custom-modal-datas form form-horizontal">
	<div class="container-fluid">
        <div class="form-group">
            <div class="col-sm-1">
                <label class="" >
                <?php eT("Modify"); ?>
                </label>
            </div>
            <div class="col-sm-11"></div>
        </div>
        <div class="form-group">
            <div class="col-sm-1">
                <label class="" >
                    <input type="checkbox" class="action_check_to_keep_old_value"></input>
                </label>
            </div>
            <label class="col-sm-3 control-label"  for='menu_id'><?php eT("Menu ID?"); ?></label>
            <div class="col-sm-8">
                <?php echo TbHtml::dropDownList('menu_id', 'lskeep', (['lskeep' => gT('Keep old value')]+$model->getMenuIdOptions()), ['disabled'=>'disabled','class'=>'custom-data selector_submitField'] );?>
            </div>
        </div>

		<div class="form-group">
            <div class="col-sm-1">
                <label class="" >
                    <input type="checkbox" class="action_check_to_keep_old_value"></input>
                </label>
            </div>
            <label class="col-sm-3 control-label"  for='menu_class'><?php eT("Menu class?"); ?></label>
            <div class="col-sm-8">
                <?php echo TbHtml::textField('menu_class', 'lskeep', array('size'=>60,'maxlength'=>255,'disabled'=>'disabled','class'=>'custom-data selector_submitField'));?>
            </div>
		</div>


		<div class="form-group">
            <div class="col-sm-1">
                <label class="" >
                    <input type="checkbox" class="action_check_to_keep_old_value"></input>
                </label>
            </div>
            <label class="col-sm-3 control-label"  for='permission'><?php eT("Permission?"); ?></label>
            <div class="col-sm-8">
                <?php echo TbHtml::textField('permission', 'lskeep', array('size'=>60,'maxlength'=>255,'disabled'=>'disabled','class'=>'custom-data selector_submitField'));?>
            </div>
		</div>

		<div class="form-group">
            <div class="col-sm-1">
                <label class="" >
                    <input type="checkbox" class="action_check_to_keep_old_value"></input>
                </label>
            </div>
            <label class="col-sm-3 control-label"  for='permission_grade'><?php eT("Permission level?"); ?></label>
            <div class="col-sm-8">
                <?php echo TbHtml::textField('permission_grade', 'lskeep', array('size'=>60,'maxlength'=>255,'disabled'=>'disabled','class'=>'custom-data selector_submitField'));?>
            </div>
		</div>

		<div class="row ls-space margin bottom-10">
			<button class="btn btn-warning pull-right " type="button" data-toggle="collapse" data-target="#collapseAdvancedOptionsMassEdit"><?php eT('Toggle advanced options') ?></button>
		</div>
		<!-- Start collapsed advanced options -->
		<div class="collapse" id="collapseAdvancedOptionsMassEdit">

            <div class="form-group">
                <div class="col-sm-1">
                    <label class="" >
                        <input type="checkbox" class="action_check_to_keep_old_value"></input>
                    </label>
                </div>
                <label class="col-sm-3 control-label"  for='permission_grade'><?php eT("User?"); ?></label>
                <div class="col-sm-8">
                    <?php echo TbHtml::dropDownList('user_id', 'lskeep', array_merge(['lskeep' => gT('Keep old value')], $model->getUserIdOptions()), ['disabled'=>'disabled','class'=>'custom-data selector_submitField'] );?>
                </div>
            </div>
            
			<div class="form-group">
                <div class="col-sm-1">
                    <label class="" >
                        <input type="checkbox" class="action_check_to_keep_old_value"></input>
                    </label>
                </div>
                <label class="col-sm-3 control-label"  for='language'><?php eT("Language?"); ?></label>
                <div class="col-sm-8">
                    <?php echo TbHtml::textField('language', 'lskeep', array('size'=>60,'maxlength'=>255,'disabled'=>'disabled', 'class'=>'custom-data selector_submitField'));?>
                </div>
			</div>
		
		</div>

		<?php echo TbHtml::hiddenField('changed_by', Yii::app()->user->id, ['class'=>'custom-data']);?>
		<?php echo TbHtml::hiddenField('changed_at', date('Y-m-d H:i:s'), ['class'=>'custom-data']);?>
		
	</div>
</form>
<!-- form -->
<script>
$('.action_check_to_keep_old_value').on('click', function(){
        var currentValue = !$(this).prop('checked');
        var myFormGroup = $(this).closest('.form-group');
        
        $(this).closest('.form-group').find('input:not(.action_check_to_keep_old_value)').prop('disabled', currentValue)
        $(this).closest('.form-group').find('select').prop('disabled', currentValue)

        if(currentValue){
            $(this).closest('.form-group').find('.selector_submitField').val('lskeep');
        } else {
            $(this).closest('.form-group').find('input.selector_submitField').val('');
        }

    });
</script>

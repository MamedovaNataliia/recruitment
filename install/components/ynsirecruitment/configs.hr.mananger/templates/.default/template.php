<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Page\Asset;
?>

    <table class="bx-edit-table ">
        <tr class="bx-top">
            <td class="bx-field-name bx-padding">
                <div>
                    <span><?=GetMessage('YNSIR_RECRUITMENT_MANAGER').':'?></span>
                </div>
            </td>
            <td>
                <div data-bx-id="task-edit-auditor" data-block-name="<?= $blockName ?>"
                     class="pinable-block task-openable-block <?= $blockClasses[$blockName] ?>">
                    <div class="task-options-item task-options-item-destination">
                        <div class="task-options-item-open-inner">
                            <?
                            $APPLICATION->IncludeComponent(
                                'bitrix:tasks.widget.member.selector',
                                '',
                                array(
                                    'TEMPLATE_CONTROLLER_ID' => 'recruitment-manager-auditor',
                                    'MAX_WIDTH' => 786,
                                    'TYPES' => array('USER', 'USER.EXTRANET', 'USER.MAIL'),
                                    'INPUT_PREFIX' => 'DATA[RECRUITMENT_MANAGER]',
                                    'ATTRIBUTE_PASS' => array(
                                        'ID',
                                        'NAME',
                                        'LAST_NAME',
                                        'EMAIL',
                                    ),
                                    'DATA' => $arResult['RECRUITMENT_MANAGER'],
                                    'PATH_TO_USER_PROFILE' => '/company/personal/user/#user_id#/',
                                ),
                                false,
                                array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
                            );
                            ?>
                        </div>
                    </div>

                </div>
            </td>
        </tr>
        <tr class="bx-top">
            <td class="bx-field-name bx-padding">
                <div>
                    <span><?=GetMessage('YNSIR_HR_MANAGER').':'?></span>
                </div>
            </td>
            <td>
                <div data-bx-id="task-edit-auditor" data-block-name="<?= $blockName ?>"
                     class="pinable-block task-openable-block <?= $blockClasses[$blockName] ?>">
                    <div class="task-options-item task-options-item-destination">
                        <div class="task-options-item-open-inner">
                            <?
                            $APPLICATION->IncludeComponent(
                                'bitrix:tasks.widget.member.selector',
                                '',
                                array(
                                    'TEMPLATE_CONTROLLER_ID' => 'hr-manager-auditor',
                                    'MAX_WIDTH' => 786,
                                    'TYPES' => array('USER', 'USER.EXTRANET', 'USER.MAIL'),
                                    'INPUT_PREFIX' => 'DATA[HR_MANAGER]',
                                    'ATTRIBUTE_PASS' => array(
                                        'ID',
                                        'NAME',
                                        'LAST_NAME',
                                        'EMAIL',
                                    ),
                                    'DATA' => $arResult['HR_MANAGER'],
                                    'PATH_TO_USER_PROFILE' => '/company/personal/user/#user_id#/',
                                ),
                                false,
                                array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
                            );
                            ?>
                        </div>
                    </div>

                </div>
            </td>
        </tr>
    </table>





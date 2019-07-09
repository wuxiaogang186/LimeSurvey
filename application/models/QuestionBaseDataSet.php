<?php
/**
 * This is a base class to enable all question tpyes to extend the general settings.
 * @TODO: Create an xml based solution to use external question type definitions as well
 */
abstract class QuestionBaseDataSet extends StaticModel
{
    private $iQuestionId;
    private $sQuestionType;
    private $sLanguage;
    private $oQuestion;
    private $aQuestionAttributes;

    public function __construct($iQuestionId)
    {
        $this->iQuestionId = $iQuestionId;
        $this->oQuestion = Question::model()->findByPk($iQuestionId);
    }

    /**
     * Returns a preformatted block of the general settings for the question editor
     *
     * @param int $iQuestionID
     * @param int $sQuestionType
     * @param int $iSurveyID
     * @param string $sLanguage
     * @return array
     */
    public function getGeneralSettingsArray($iQuestionID = null, $sQuestionType = null, $sLanguage = null)
    {
        if ($iQuestionID != null) {
            $this->oQuestion = Question::model()->findByPk($iQuestionID);
        } else {
            $iSurveyId = Yii::app()->request->getParam('sid') ?? Yii::app()->request->getParam('surveyid');
            $this->oQuestion = $oQuestion = QuestionCreate::getInstance($iSurveyId, $sQuestionType);
        }
        
        $this->sQuestionType = $sQuestionType == null ? $this->oQuestion->type : $sQuestionType;
        $this->sLanguage = $sLanguage == null ? $this->oQuestion->survey->language : $sLanguage;
        $this->aQuestionAttributes = QuestionAttribute::model()->getQuestionAttributes($this->oQuestion->qid, $this->sLanguage);
        /*
        @todo Discussion:
        General options currently are
        - Question theme => this should have a seperate advanced tab in my opinion
        - Question group
        - Mandatory switch
        - Save as default switch
        - Clear default switch (if default value record exists)
        - Relevance equation
        - Validation => this is clearly a logic function
        
        Better add to general options:
        - Hide Tip => VERY OFTEN asked for
        - Always hide question => if available
        */
        $returnArray = [
            'question_template' => $this->getQuestionThemeOption(),
            'gid' => $this->getQuestionGroupSelector(),
            'other' => $this->getOtherSwitch(),
            'mandatory' => $this->getMandatorySetting(),
            'relevance' => $this->getRelevanceEquationInput(),
            'encrypted' => $this->getEncryptionSwitch(),
            'save_as_default' => $this->getSaveAsDefaultSwitch()
        ];
        
        $userSetting = SettingsUser::getUserSettingValue('question_default_values_' . $this->sQuestionType);
        if ($userSetting !== null){
            $returnArray['clear_default'] = $this->getClearDefaultSwitch();
        }

        $returnArray['preg'] = $this->getValidationInput();

        return $returnArray;
    }

    /**
     * Returns a preformatted block of the advanced settings for the question editor
     *
     * @param int $iQuestionID
     * @param int $sQuestionType
     * @param int $iSurveyID
     * @param string $sLanguage
     * @return array
     */
    public function getAdvancedOptions($iQuestionID = null, $sQuestionType = null, $sLanguage = null,  $sQuestionTemplate = null)
    {
        if ($iQuestionID != null) {
            $this->oQuestion = Question::model()->findByPk($iQuestionID);
        } else {
            $iSurveyId = Yii::app()->request->getParam('sid') ?? Yii::app()->request->getParam('surveyid');
            $this->oQuestion = $oQuestion = QuestionCreate::getInstance($iSurveyId, $sQuestionType);
        }
        
        $this->sQuestionType = $sQuestionType == null ? $this->oQuestion->type : $sQuestionType;
        $this->sLanguage = $sLanguage == null ? $this->oQuestion->survey->language : $sLanguage;
        $this->aQuestionAttributes = QuestionAttribute::model()->getQuestionAttributes($this->oQuestion->qid, $this->sLanguage);
        
        $sQuestionType = $this->sQuestionType;
        if($this->aQuestionAttributes['question_template'] !== 'core' && $sQuestionTemplate === null) {
            $sQuestionTemplate = $this->aQuestionAttributes['question_template'];
        }
        $sQuestionTemplate = $sQuestionTemplate == '' || $sQuestionTemplate == 'core' ? null : $sQuestionTemplate;
        $questionTemplateFolderName = QuestionTemplate::getFolderName($this->sQuestionType);
        $aQuestionTypeAttributes = \LimeSurvey\Helpers\questionHelper::getQuestionThemeAttributeValues($questionTemplateFolderName, $sQuestionTemplate);

        $aAdvancedOptionsArray = [];
        if ($iQuestionID == null) {
            $userSetting = SettingsUser::getUserSettingValue('question_default_values_' . $this->oQuestion->type);
            if ($userSetting !== null){
                $aAdvancedOptionsArray = (array) json_decode($userSetting);
            }
        } 
        
        if (empty($aAdvancedOptionsArray)){
            foreach ($aQuestionTypeAttributes as $sAttributeName => $aQuestionAttributeArray) {
                $aAdvancedOptionsArray[$aQuestionAttributeArray['category']][$sAttributeName] = $this->parseFromAttributeHelper($sAttributeName, $aQuestionAttributeArray);
            }
        }
        
        return $aAdvancedOptionsArray;
    }

    //Question theme
    protected function getQuestionThemeOption()
    {
        $aQuestionTemplateList = QuestionTemplate::getQuestionTemplateList($this->sQuestionType);
        $aQuestionTemplateAttributes = Question::model()->getAdvancedSettingsWithValues($this->oQuestion->qid, $this->sQuestionType, $this->oQuestion->survey->sid)['question_template'];

        $aOptionsArray = [];
        foreach ($aQuestionTemplateList as $code => $value) {
            $aOptionsArray[] = [
                'value' => $code,
                'text' => $value['title']
            ];
        }

        return [
                'name' => 'question_template',
                'title' => gT('Question theme'),
                'formElementId' => 'question_template',
                'formElementName' => false, //false means identical to id
                'formElementHelp' => gT("Use a customized question theme for this question"),
                'inputtype' => 'select',
                'formElementValue' => (isset($aQuestionTemplateAttributes['value']) &&  $aQuestionTemplateAttributes['value'] !== '') ? $aQuestionTemplateAttributes['value'] : 'core',
                'formElementOptions' => [
                    'classes' => ['form-control'],
                    'options' => $aOptionsArray,
                ],
            ];
    }

    //Question group
    protected function getQuestionGroupSelector()
    {
        $aGroupsToSelect = QuestionGroup::model()->findAllByAttributes(array('sid' => $this->oQuestion->sid), array('order'=>'group_order'));
        $aGroupOptions = [];
        array_walk(
            $aGroupsToSelect,
            function ($oQuestionGroup) use (&$aGroupOptions){
                $aGroupOptions[] = [
                    'value' => $oQuestionGroup->gid,
                    'text' => $oQuestionGroup->questionGroupL10ns[$this->sLanguage]->group_name,
                ];
            }
        );

        return [
            'name' => 'gid',
            'title' => gT('Question group'),
            'formElementId' => 'gid',
            'formElementName' => false,
            'formElementHelp' => gT("If you want to change the question group this question is in."),
            'inputtype' => 'select',
            'formElementValue' => $this->oQuestion->gid,
            'formElementOptions' => [
                'classes' => ['form-control'],
                'options' => $aGroupOptions,
            ],
            'disableInActive' => true
        ];
    }

    protected function getOtherSwitch()
    {
        return  [
                'name' => 'other',
                'title' => gT('Other'),
                'formElementId' => 'other',
                'formElementName' => false,
                'formElementHelp' => gT('Activate the "other" option for your question'),
                'inputtype' => 'switch',
                'formElementValue' => $this->oQuestion->other,
                'formElementOptions' => [
                    'classes' => [],
                    'options' => [
                        'option' => [
                            [
                                'text' => gT("On"),
                                'value' => 'Y'
                            ],
                            [
                                'text' => gT("Off"),
                                'value' => 'N'
                            ],
                        ]
                    ],
                ],
                'disableInActive' => true
            ];
    }

    protected function getMandatorySetting()
    {
        return [
                'name' => 'mandatory',
                'title' => gT('Mandatory'),
                'formElementId' => 'mandatory',
                'formElementName' => false,
                'formElementHelp' => gT('Makes this question mandatory in your survey'),
                'inputtype' => 'buttongroup',
                'formElementValue' => $this->oQuestion->mandatory,
                'formElementOptions' => [
                    'classes' => [],
                    'options' => [
                        [
                            'text' => gT("On"),
                            'value' => 'Y'
                        ],
                        [
                            'text' => gT("Soft"),
                            'value' => 'S'
                        ],
                        [
                            'text' => gT("Off"),
                            'value' => 'N'
                        ],
                    ],
                ]
            ];
    }

    protected function getEncryptionSwitch()
    {
        return [
                'name' => 'encrypted',
                'title' => gT('Encrypted'),
                'formElementId' => 'encrypted',
                'formElementName' => false,
                'formElementHelp' => gT('Store the answers to this question encrypted'),
                'inputtype' => 'switch',
                'formElementValue' => $this->oQuestion->encrypted,
                'formElementOptions' => [
                    'classes' => [],
                    'options' => [
                        'option' => [
                            [
                                'text' => gT("On"),
                                'value' => 'Y'
                            ],
                            [
                                'text' => gT("Off"),
                                'value' => 'N'
                            ],
                        ]
                    ],
                ],
                'disableInActive' => true
            ];
    }

    protected function getSaveAsDefaultSwitch()
    {
        return [
                'name' => 'save_as_default',
                'title' => gT('Save as default values'),
                'formElementId' => 'save_as_default',
                'formElementName' => false,
                'formElementHelp' => gT('All attribute values for this question type will be saved as default'),
                'inputtype' => 'switch',
                'formElementValue' => '',
                'formElementOptions' => [
                    'classes' => [],
                    'options' => [
                        'option' => [
                            [
                                'text' => gT("On"),
                                'value' => 'Y'
                            ],
                            [
                                'text' => gT("Off"),
                                'value' => 'N'
                            ],
                        ]
                    ],
                ],
            ];
    }

    protected function getClearDefaultSwitch()
    {
        return [
                'name' => 'clear_default',
                'title' => gT('Clear default values'),
                'formElementId' => 'clear_default',
                'formElementName' => false,
                'formElementHelp' => gT('Default attribute values for this question type will be cleared'),
                'inputtype' => 'switch',
                'formElementValue' => '',
                'formElementOptions' => [
                    'classes' => [],
                    'options' => [
                        'option' => [
                            [
                                'text' => gT("On"),
                                'value' => 'Y'
                            ],
                            [
                                'text' => gT("Off"),
                                'value' => 'N'
                            ],
                        ]
                    ],
                ],
            ];
    }
        
    protected function getRelevanceEquationInput()
    {
        $inputtype = 'textarea';
        
        if (count($this->oQuestion->conditions) > 0) {
            $inputtype = 'text';
            $content = gT("Note: You can't edit the relevance equation because there are currently conditions set for this question.");
        }

        return [
                'name' => 'relevance',
                'title' => gT('Relevance equation'),
                'formElementId' => 'relevance',
                'formElementName' => false,
                'formElementHelp' => (count($this->oQuestion->conditions)>0 ? '' :gT("The relevance equation can be used to add branching logic. This is a rather advanced topic. If you are unsure, just leave it be.")),
                'inputtype' => 'textarea',
                'formElementValue' => $this->oQuestion->relevance,
                'formElementOptions' => [
                    'classes' => ['form-control'],
                    'attributes' => [
                        'rows' => 1,
                        'readonly' => count($this->oQuestion->conditions)>0
                    ],
                    'inputGroup' => [
                        'prefix' => '{',
                        'suffix' => '}',
                    ]
                ],
            ];
    }
            
    protected function getValidationInput()
    {
        return  [
                'name' => 'validation',
                'title' => gT('Input validation'),
                'formElementId' => 'preg',
                'formElementName' => false,
                'formElementHelp' => gT('You can add any regular expression based validation in here'),
                'inputtype' => 'text',
                'formElementValue' => $this->oQuestion->preg,
                'formElementOptions' => [
                    'classes' => ['form-control'],
                    'inputGroup' => [
                        'prefix' => 'RegExp',
                    ]
                ],
            ];
    }

    protected function parseFromAttributeHelper($sAttributeKey, $aAttributeArray)
    {
        $aAdvancedAttributeArray = [
            'name' => $sAttributeKey,
            'title' => CHtml::decode($aAttributeArray['caption']),
            'inputtype' => $aAttributeArray['inputtype'],
            'formElementId' => $sAttributeKey,
            'formElementName' => false,
            'formElementHelp' => $aAttributeArray['help'],
            'formElementValue' => isset($this->aQuestionAttributes[$sAttributeKey]) ? $this->aQuestionAttributes[$sAttributeKey] : ''
        ];
        unset($aAttributeArray['caption']);
        unset($aAttributeArray['help']);
        unset($aAttributeArray['inputtype']);

        $aFormElementOptions = $aAttributeArray;
        $aFormElementOptions['classes'] = isset($aFormElementOptions['classes']) ? array_merge($aFormElementOptions['classes'], ['form-control']) : ['form-control'];

        if(!is_array($aFormElementOptions['expression']) && $aFormElementOptions['expression'] == 2) {
            $aFormElementOptions['inputGroup'] = [
                'prefix' => '{',
                'suffix' => '}',
                ];
        }

        $aAdvancedAttributeArray['aFormElementOptions'] = $aFormElementOptions;
        
        return $aAdvancedAttributeArray;
    }
}
<?php
App::import('Model', 'Transcription');

class TranscriptionTestCase extends CakeTestCase {
    var $fixtures = array(
        'app.contribution',
        'app.country',
        'app.favorites_user',
        'app.group',
        'app.language',
        'app.link',
        'app.sentence',
        'app.sentence_annotation',
        'app.sentence_comment',
        'app.sentences_list',
        'app.sentences_sentences_list',
        'app.tag',
        'app.tags_sentence',
        'app.transcription',
        'app.user',
        'app.wall',
        'app.wall_thread',
    );

    function startTest() {
        $this->Transcription =& ClassRegistry::init('Transcription');
    }

    function endTest() {
        unset($this->Transcription);
        ClassRegistry::flush();
    }

    function _getRecord($record) {
        return $this->_fixtures['app.transcription']->records[$record];
    }

    function _saveRecordWith($record, $changedFields) {
        $data = $this->_getRecord($record);
        $this->Transcription->deleteAll(array('1=1'));
        unset($data['id']);
        $data = array_merge($data, $changedFields);
        return (bool)$this->Transcription->save($data);
    }

    function _saveRecordWithout($record, $missingFields) {
        $data = $this->_getRecord($record);
        $this->Transcription->deleteAll(array('1=1'));
        unset($data['id']);
        foreach ($missingFields as $field) {
            unset($data[$field]);
        }
        return (bool)$this->Transcription->save($data);
    }

    function _assertValidRecordWith($record, $changedFields) {
        $this->assertTrue($this->_saveRecordWith($record, $changedFields));
    }
    function _assertValidRecordWithout($record, $changedFields) {
        $this->assertTrue($this->_saveRecordWithout($record, $changedFields));
    }
    function _assertInvalidRecordWith($record, $changedFields) {
        $this->assertFalse($this->_saveRecordWith($record, $changedFields));
    }
    function _assertInvalidRecordWithout($record, $missingFields) {
        $this->assertFalse($this->_saveRecordWithout($record, $missingFields));
    }

    function testValidateFirstRecord() {
        $this->_assertValidRecordWith(0, array());
    }

    function testScriptMustBeValid() {
        $this->_assertInvalidRecordWith(0, array('script' => 'ABCD'));
    }
    function testScriptRequired() {
        $this->_assertInvalidRecordWithout(0, array('script'));
    }
    function testScriptCantBeUpdated() {
        $this->Transcription->delete(1); // to avoid uniqness error
        $data = array('id' => 2, 'script' => 'Hrkt');

        $result = (bool)$this->Transcription->save($data);

        $this->assertFalse($result);
    }

    function testTextCantBeEmpty() {
        $this->_assertInvalidRecordWith(0, array('text' => ''));
    }
    function testTextRequired() {
        $this->_assertInvalidRecordWithout(0, array('text'));
    }

    function testSentenceIdCantBeEmpty() {
        $this->_assertInvalidRecordWith(0, array('sentence_id' => null));
    }
    function testSentenceIdRequired() {
        $this->_assertInvalidRecordWithout(0, array('sentence_id'));
    }
    function testSentenceIdCantBeUpdated() {
        $this->Transcription->delete(3); // to avoid uniqness error
        $data = array('id' => 1, 'sentence_id' => 10);

        $result = (bool)$this->Transcription->save($data);

        $this->assertFalse($result);
    }

    function testParentIdMustBeNumeric() {
        $this->_assertInvalidRecordWith(0, array('parent_id' => 'melon'));
    }
    function testParentIdNotRequired() {
        $this->_assertValidRecordWithout(0, array('parent_id'));
    }

    function testCreatedCantBeEmpty() {
        $this->_assertInvalidRecordWith(0, array('created' => ''));
    }
    function testCreatedIsAutomaticallySet() {
        $this->_assertValidRecordWithout(0, array('created'));
    }

    function testUserIdMustBeNumeric() {
        $this->_assertInvalidRecordWith(0, array('user_id' => 'melon'));
    }
    function testUserIdNotRequired() {
        $this->_assertValidRecordWithout(0, array('user_id'));
    }

    function testModifiedCantBeEmpty() {
        $this->_assertInvalidRecordWith(0, array('modified' => ''));
    }
    function testModifiedIsAutomaticallySet() {
        $this->_assertValidRecordWithout(0, array('created'));
    }

    function testTranscriptionMustBeUniqueForASentenceAndAScriptOnCreate() {
        $data = $this->_getRecord(0);
        unset($data['id']);

        $result = (bool)$this->Transcription->save($data);

        $this->assertFalse($result);
    }

    function testJapaneseCanBeTranscriptedToKanas() {
        $jpnSentence = $this->Transcription->Sentence->find('first', array(
            'conditions' => array('Sentence.lang' => 'jpn')
        ));
        $result = $this->Transcription->transcriptableToWhat($jpnSentence);
        $this->assertTrue(isset($result['Hrkt']));
    }
    function testJapaneseCanBeTranscriptedToRomaji() {
        $jpnSentence = $this->Transcription->Sentence->find('first', array(
            'conditions' => array('Sentence.lang' => 'jpn')
        ));
        $result = $this->Transcription->transcriptableToWhat($jpnSentence);
        $this->assertTrue(isset($result['Latn']));
    }

    function testEditTrancriptionText() {
        $result = $this->Transcription->save(array(
            'id' => 3, 'text' => 'we change this'
        ));
        $this->assertTrue($result);
    }
    function testEditTrancriptionTextCantBeEmpty() {
        $result = $this->Transcription->save(array(
            'id' => 3, 'text' => ''
        ));
        $this->assertFalse($result);
    }

    function testCantSaveTranscriptionWithInvalidParent() {
        $nonexistantSentenceId = 52715278;
        $result = $this->Transcription->save(array(
            'sentence_id' => $nonexistantSentenceId,
            'script' => 'Latn',
            'text' => 'Transcription with invalid parent.',
        ));
        $this->assertFalse($result);
    }

    function testCantSaveNotAllowedTranscriptionOnInsert() {
        $englishSentenceId = 1;
        $result = $this->Transcription->save(array(
            'sentence_id' => $englishSentenceId,
            'script' => 'Latn',
            'text' => 'Transcript of English into Latin script??',
        ));
        $this->assertFalse($result);
    }

    function testCantSaveNotAllowedTranscriptionOnUpdate() {
        $result = $this->Transcription->save(array(
            'id' => 1,
            'script' => 'Jpan',
            'text' => 'Transcript of Japanese into Japanese??',
        ));
        $this->assertFalse($result);
    }

    function _installAutotranscriptionMock() {
        Mock::generate('Autotranscription');
        $autotranscription =& new MockAutotranscription;
        $this->Transcription->setAutotranscription($autotranscription);
        return $autotranscription;
    }

    function testGenerateTranscriptionCallsGenerator() {
        $jpnSentence = $this->Transcription->Sentence->find('first', array(
            'conditions' => array('Sentence.lang' => 'jpn')
        ));
        $this->_installAutotranscriptionMock()->expectOnce(
            '_getFurigana',
            array($jpnSentence['Sentence']['text'])
        );

        $this->Transcription->generateTranscription($jpnSentence, 'Hrkt');
    }

    function testGenerateTranscriptionChains() {
        $jpnSentence = $this->Transcription->Sentence->find('first', array(
            'conditions' => array('Sentence.lang' => 'jpn')
        ));
        $mock = $this->_installAutotranscriptionMock();
        $mock->setReturnValue('_getFurigana', 'あああ');
        $mock->expectOnce(
            'tokenizedJapaneseWithReadingsToRomaji',
            array('あああ')
        );

        $this->Transcription->generateTranscription($jpnSentence, 'Hrkt');
    }

    function testGenerateTranscriptionReturnsTranscription() {
        $jpnSentence = $this->Transcription->Sentence->findById(6);
        $this->_installAutotranscriptionMock()->setReturnValue('_getFurigana', 'stuff');

        $result = $this->Transcription->generateTranscription($jpnSentence, 'Hrkt');
        $expected = array(array(
            'sentence_id' => 6,
            'parent_id' => null,
            'script' => 'Hrkt',
            'text' => 'stuff',
            'user_id' => null,
            'readonly' => false,
            'id' => 'autogenerated',
        ));
        $this->assertEqual($expected, $result);
    }

    function testGenerateTranscriptionReturnsTranscriptionWithParent() {
        $this->Transcription->deleteAll('1=1');
        $jpnSentence = $this->Transcription->Sentence->findById(6);
        $mock = $this->_installAutotranscriptionMock();
        $mock->setReturnValue('_getFurigana', 'stuff in furigana');
        $mock->setReturnValue('tokenizedJapaneseWithReadingsToRomaji',
                              'stuff in Latin');
        $expected = array(
            array(
                'id' => 'autogenerated',
                'sentence_id' => 6,
                'parent_id' => null,
                'script' => 'Hrkt',
                'text' => 'stuff in furigana',
                'user_id' => null,
                'readonly' => false,
            ),
            array(
                'id' => null,
                'sentence_id' => 6,
                'parent_id' => 'autogenerated',
                'script' => 'Latn',
                'text' => 'stuff in Latin',
                'user_id' => null,
                'readonly' => true,
            )
        );

        $result = $this->Transcription->generateTranscription($jpnSentence, 'Hrkt');

        $this->assertEqual($expected, $result);
    }

    function testGenerateTranscriptionCreatesGenerated() {
        $this->Transcription->deleteAll('1=1');
        $jpnSentence = $this->Transcription->Sentence->findById(10);
        $mock = $this->_installAutotranscriptionMock();
        $mock->setReturnValue('tokenizedJapaneseWithReadingsToRomaji', 'stuff in Latin');
        $mock->setReturnValue('_getFurigana', 'stuff in kana');

        $this->Transcription->generateTranscription($jpnSentence, 'Hrkt', true);

        $created = $this->Transcription->find('count');
        $this->assertEqual(2, $created);
    }

    function testGenerateTranscriptionCreatesProvidedTranscription() {
        $this->Transcription->deleteAll('1=1');
        $jpnSentence = $this->Transcription->Sentence->findById(10);
        $mock = $this->_installAutotranscriptionMock();
        $mock->setReturnValue('tokenizedJapaneseWithReadingsToRomaji', 'stuff in Latin');
        $data = array(
            'text' => 'あああ',
            'sentence_id' => 10,
            'script' => 'Hrkt',
            'user_id' => 33,
        );

        $this->Transcription->generateTranscription($jpnSentence, 'Hrkt', true, $data);

        $created = $this->Transcription->find('all');
        $created = array_map(
            function ($r) {
                unset($r['Transcription']['modified']);
                unset($r['Transcription']['created']);
                return $r;
            },
            $created
        );
        $expected = array(
            array('Transcription' => array(
                'id' => 5,
                'sentence_id' => 10,
                'parent_id' => 4,
                'script' => 'Latn',
                'text' => 'stuff in Latin',
                'user_id' => null,
            )),
            array('Transcription' => array(
                'id' => 4,
                'sentence_id' => 10,
                'parent_id' => null,
                'script' => 'Hrkt',
                'text' => 'あああ',
                'user_id' => 33,
            )),
        );
        $this->assertEqual($expected, $created);
    }

    function testGenerateTranscriptionUpdates() {
        $mock = $this->_installAutotranscriptionMock();
        $mock->setReturnValue('tokenizedJapaneseWithReadingsToRomaji', 'stuff in Latin');
        $transcr = $this->Transcription->findById(1);
        $jpnSentence = $this->Transcription->Sentence->findById(
            $transcr['Transcription']['sentence_id']
        );
        $transcr['Transcription']['text'] = 'あああ';

        $this->Transcription->generateTranscription(
            $jpnSentence, 'Hrkt', true, $transcr['Transcription']
        );

        $updated = $this->Transcription->find('all', array(
            'conditions' => array(
                'sentence_id' => $transcr['Transcription']['sentence_id']
            )
        ));
        $this->assertEqual('あああ', $updated[0]['Transcription']['text']);
        $this->assertEqual('stuff in Latin', $updated[1]['Transcription']['text']);
    }

    function testGenerateAndSaveAllTranscriptionsFor() {
        $this->Transcription->deleteAll('1=1');
        $jpnSentence = $this->Transcription->Sentence->findById(6);
        $mock = $this->_installAutotranscriptionMock();
        $mock->setReturnValue('tokenizedJapaneseWithReadingsToRomaji', 'stuff in Latin');
        $mock->setReturnValue('_getFurigana', 'stuff in kana');

        $this->Transcription->generateAndSaveAllTranscriptionsFor($jpnSentence);

        $created = $this->Transcription->find('count', array(
            'conditions' => array('sentence_id' => 6)
        ));
        $this->assertEqual(2, $created);
    }

    function testCanCreateReadonlyTranscriptions() {
        $this->_assertValidRecordWith(1, array());
    }

    function testCannotUpdateReadonlyTranscriptions() {
        $result = (bool)$this->Transcription->saveTranscription(array(
            'id' => 2,
            'sentence_id' => 6,
            'parent_id' => 1,
            'script' => 'Latn',
            'text' => 'blah blah blah',
        ));
        $this->assertFalse($result);
    }

    function testAddGeneratedTranscriptionsAddsEverything() {
        $this->Transcription->deleteAll('1=1');
        $jpnSentence = $this->Transcription->Sentence->findById(10);

        $result = $this->Transcription->addGeneratedTranscriptions(
            array(),
            $jpnSentence
        );

        $this->assertEqual(2, count($result));
        $this->assertEqual('Hrkt', $result[0]['script']);
        $this->assertEqual('Latn', $result[1]['script']);
    }

    function testAddGeneratedTranscriptionsDontDoubleGenerate() {
        $this->Transcription->deleteAll('1=1');
        $jpnSentence = $this->Transcription->Sentence->findById(10);

        Mock::generatePartial('Transcription', 'MockTranscription', array('generateTranscription'));
        $this->Transcription =& new MockTranscription;
        $this->Transcription->setReturnValue('generateTranscription', array());
        $this->Transcription->expectCallCount('generateTranscription', 1);

        $this->Transcription->addGeneratedTranscriptions(
            array(),
            $jpnSentence
        );
    }

    function testAddGeneratedTranscriptionsAddsNothing() {
        $jpnSentence = $this->Transcription->Sentence->findById(6);
        $existingTranscriptions = $this->Transcription->find(
            'all',
            array('conditions' => array('sentence_id' => 6))
        );
        $existingTranscriptions = Set::classicExtract(
            $existingTranscriptions,
            '{n}.Transcription'
        );

        $result = $this->Transcription->addGeneratedTranscriptions(
            $existingTranscriptions,
            $jpnSentence
        );

        $this->assertEqual(2, count($result));
        $this->assertEqual('Hrkt', $result[0]['script']);
        $this->assertEqual('Latn', $result[1]['script']);
    }
}

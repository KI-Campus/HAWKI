<?php

$encryptionSalt = isset($env) ? $env['CHATLOG_ENCRYPTION_SALT'] : getenv('CHATLOG_ENCRYPTION_SALT');
$userSpecificSalt = $encryptionSalt . $_SESSION['username'];

?>

<script>

    function saveMessagesToLocalStorage() {
        const username = '<?= htmlspecialchars($_SESSION['username']) ?>'; // Use the PHP variable
        const messagesElement = document.querySelector(".messages");
        const messageElements = messagesElement.querySelectorAll(".message");
        let requestObject = { messages: [] };

        messageElements.forEach(messageElement => {
            let messageObject = {};
            messageObject.role = messageElement.dataset.role;
            messageObject.content = messageElement.querySelector(".message-text").textContent;
            requestObject.messages.push(messageObject);
        });

        // Convert messages to string
        const messageString = JSON.stringify(requestObject.messages);
        const compressedMessages = LZString.compressToUTF16(messageString);

        const salt = '<?= htmlspecialchars($userSpecificSalt) ?>';

        // Derive a key from the username
        const key = CryptoJS.PBKDF2(username, CryptoJS.enc.Hex.parse(salt), {
            keySize: 256 / 32,
            iterations: 1000
        });

        // Encrypt the messages
        const encrypted = CryptoJS.AES.encrypt(compressedMessages, key.toString());
        const storageDate = Date.now();

        const storagePackage = JSON.stringify({
            encryptedData: encrypted.toString(),
            storageDate: storageDate,
        })

        // Save encrypted data to local storage
        localStorage.setItem('chatLog_' + username, storagePackage);
    }



    function loadMessagesFromLocalStorage() {

        const username = '<?= htmlspecialchars($_SESSION['username']) ?>'; // Use the PHP variable
        const storedData = localStorage.getItem('chatLog_' + username);
        if(storedData === null){
            return;
        }
        const parsedData = JSON.parse(storedData);
        const encryptedData = parsedData.encryptedData;
        const salt = '<?= htmlspecialchars($userSpecificSalt) ?>';

        if (encryptedData) {
            try {
                // Derive the key from the username
                const key = CryptoJS.PBKDF2(username, CryptoJS.enc.Hex.parse(salt), {
                    keySize: 256 / 32,
                    iterations: 1000
                });

                // Decrypt the messages
                const decrypted = CryptoJS.AES.decrypt(encryptedData, key.toString());
                const decryptedString = decrypted.toString(CryptoJS.enc.Utf8);

                const decompressedString = LZString.decompressFromUTF16(decryptedString)
                const messages = JSON.parse(decompressedString);
                
                
                if(messages != null){
                    document.querySelector('.limitations')?.remove();
                }


                messages.forEach(message => {
                    addMessage(message);
                });
            } catch (error) {
                console.error("Failed to decrypt or parse messages:", error);
            }
        }
    }


    function cleanupStoredLogs(){
        const items = {};
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key.startsWith("chatLog_")) {
                const storedData = localStorage.getItem(localStorage.key(i));
                const parsedData = JSON.parse(storedData);

                // check if the stored data is older than one week
                if(Date.now() > parsedData.storageDate + 7 * 24 * 60 * 60 * 1000){
                    localStorage.removeItem(localStorage.key(i));
                }
            }
        }
    }


    function deleteChatLog(){
        const username = '<?= htmlspecialchars($_SESSION['username']) ?>'; // Use the PHP variable
        localStorage.removeItem('chatLog_' + username);
        const chatBtn = document.querySelector("#chatMenuButton");
        load(chatBtn ,'chat.php');
    }
    function openDeletePanel(){
        document.getElementById('delete-chat-confirm').style.display = "flex";
    }
    function cancelDelete(){
        document.getElementById('delete-chat-confirm').style.display = "none";
    }

</script>

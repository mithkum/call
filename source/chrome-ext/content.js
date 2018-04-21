/**
 * mgVideoChat this content script
 * publish/subscribe messages from webpage to the background script,
 * receives those messages and forwards to background script
 *
 * @version 1.0
 * @copyright magnoliyan
 */
window.addEventListener('message', function (event) {
    // if invalid source
    if (event.source != window){
        return;
    }
    
    console.log('content event received',event.data);
    
    switch(event.data.message){
        case 'mgIsLoaded':
            window.postMessage({message: 'mgIsLoadedResult'}, '*');
            break;
        case 'mgGetSourceId':
            chrome.runtime.sendMessage({message: "mgGetSourceId"});            
            break;
        default:
            return;
    }
});

//messages from background script
chrome.runtime.onMessage.addListener(function(request, sender, sendResponse) {
    console.log(sender.tab ? "from a content script:" + sender.tab.url : "from the extension");
    if (request.message == "mgGetSourceIdResult"){
        console.log(request);
        window.postMessage(request, '*');
    }
});
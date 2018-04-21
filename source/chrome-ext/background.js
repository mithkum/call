/**
 * mgVideoChat this background script is used to invoke desktopCapture API
 *
 * @version 1.0
 * @copyright magnoliyan
 */
chrome.runtime.onMessage.addListener(function(request, sender, sendResponse) {
    console.log(sender.tab ? "from a content script:" + sender.tab.url : "from the extension");
    if (request.message != "mgGetSourceId"){
        sendResponse({
            message:"mgGetSourceIdResult", 
            success: false
        });
        return false;
    }    
        
    chrome.desktopCapture.chooseDesktopMedia(['screen', 'window'], sender.tab, function(sourceId){
        console.log('sourceId', sourceId);        
        // if "cancel" button is clicked
        if(!sourceId || !sourceId.length) {
            chrome.tabs.sendMessage(sender.tab.id, {
                message:"mgGetSourceIdResult",
                success: false, 
                error: 'PermissionDeniedError'
            });
            return false;
        }
        
        chrome.tabs.sendMessage(sender.tab.id, {
            message:"mgGetSourceIdResult",
            success: true, 
            sourceId: sourceId
        });                    
    });
    
    return;
});

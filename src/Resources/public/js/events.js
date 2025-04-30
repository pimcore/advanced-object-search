/**
* This source file is available under the terms of the
* Pimcore Open Core License (POCL)
* Full copyright and license information is available in
* LICENSE.md which is distributed with this source code.
*
*  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.com)
*  @license    Pimcore Open Core License (POCL)
*/

/**
 * on search result initialize
 * extensionBag is passed as parameters
 */
pimcore.events.onAdvancedObjectSearchResult = "pimcore.advancedObjectSearch.result.initialize";

pimcore.events.onAdvancedObjectSearchResultRowContextMenu = "pimcore.advancedObjectSearch.result.onRowContextMenu";

//TODO: delete once support for Pimcore 10.6 is dropped

if(typeof addEventListenerCompatibilityForPlugins === "function") {
    let eventMappings = [];
    eventMappings["onAdvancedObjectSearchResult"] = pimcore.events.onAdvancedObjectSearchResult;
    addEventListenerCompatibilityForPlugins(eventMappings);
    console.warn("Deprecation: addEventListenerCompatibilityForPlugins will be not supported in Pimcore 11.");

}

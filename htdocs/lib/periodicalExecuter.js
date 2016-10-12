/**
 * @author Harmen Janssen <http://www.whatstyle.net>
 * See http://www.whatstyle.net/articles/36/javascript_periodical_executer for more information
 * 
 */
Function.prototype.executePeriodically = function (){
	var s = this;
	if (typeof arguments[0].callee != 'undefined'){
		var arquments = arguments[0];
	} else {
		var arquments = arguments;
	}
	
	var delay = arquments[0];
	var timesToExecute = arquments[1];
	this.__INTERVAL__ = null;
	
	var args = [];
	for (var i=2; i<arquments.length; i++){ args.push(arquments[i]); }
	
	s.apply(this,args);
	
	if (this.__INTERVAL__)
		clearTimeout(this.__INTERVAL__);
	
	if (--timesToExecute > 0){
		this.__INTERVAL__ = setTimeout (function (){
			arquments[1] = timesToExecute;
			s.executePeriodically(arquments);
		},delay);
	}
	return s;
}
Clazz.declarePackage ("J.adapter.readers.cif");
Clazz.load (null, "J.adapter.readers.cif.Subsystem", ["JU.List", "$.Matrix", "$.V3", "J.util.Logger"], function () {
c$ = Clazz.decorateAsClass (function () {
this.msReader = null;
this.code = null;
this.d = 0;
this.w = null;
this.symmetry = null;
this.modMatrices = null;
Clazz.instantialize (this, arguments);
}, J.adapter.readers.cif, "Subsystem");
Clazz.makeConstructor (c$, 
function (msReader, code, w) {
this.msReader = msReader;
this.code = code;
this.w = w;
this.d = w.getArray ().length - 3;
}, "J.adapter.readers.cif.MSReader,~S,JU.Matrix");
$_M(c$, "getSymmetry", 
function () {
if (this.modMatrices == null) this.setSymmetry ();
return this.symmetry;
});
$_M(c$, "getModMatrices", 
function () {
if (this.modMatrices == null) this.setSymmetry ();
return this.modMatrices;
});
$_M(c$, "setSymmetry", 
($fz = function () {
var a;
var w33 = this.w.getSubmatrix (0, 0, 3, 3);
var wd3 = this.w.getSubmatrix (3, 0, this.d, 3);
var w3d = this.w.getSubmatrix (0, 3, 3, this.d);
var wdd = this.w.getSubmatrix (3, 3, this.d, this.d);
var sigma = this.msReader.getSigma ();
var sigma_nu = wdd.mul (sigma).add (wd3).mul (w3d.mul (sigma).add (w33).inverse ());
var tFactor = wdd.sub (sigma_nu.mul (w3d));
this.modMatrices = [sigma_nu, tFactor];
var s0 = this.msReader.cr.atomSetCollection.symmetry;
var vu43 = s0.getUnitCellVectors ();
var vr43 = this.reciprocalsOf (vu43);
var mard3 =  new JU.Matrix (null, 3 + this.d, 3);
var mar3 =  new JU.Matrix (null, 3, 3);
var mard3a = mard3.getArray ();
var mar3a = mar3.getArray ();
for (var i = 0; i < 3; i++) mard3a[i] = mar3a[i] = [vr43[i + 1].x, vr43[i + 1].y, vr43[i + 1].z];

var sx = sigma.mul (mar3);
a = sx.getArray ();
for (var i = 0; i < this.d; i++) mard3a[i + 3] = a[i];

a = this.w.mul (mard3).getArray ();
var uc_nu =  new Array (4);
uc_nu[0] = vu43[0];
for (var i = 0; i < 3; i++) uc_nu[i + 1] = JU.V3.new3 (a[i][0], a[i][1], a[i][2]);

uc_nu = this.reciprocalsOf (uc_nu);
this.symmetry = this.msReader.cr.symmetry.getUnitCell (uc_nu, false);
var winv = this.w.inverse ();
J.util.Logger.info ("[subsystem " + this.code + "]");
this.symmetry.createSpaceGroup (-1, "[subsystem " + this.code + "]",  new JU.List ());
var nOps = s0.getSpaceGroupOperationCount ();
for (var iop = 0; iop < nOps; iop++) {
var rv = s0.getOperationRsVs (iop);
var r = this.w.mul (rv.getRotation ()).mul (winv);
var v = this.w.mul (rv.getTranslation ());
var jf = this.symmetry.addOp (r, v);
J.util.Logger.info (jf);
}
}, $fz.isPrivate = true, $fz));
$_M(c$, "reciprocalsOf", 
($fz = function (abc) {
var rabc =  new Array (4);
rabc[0] = abc[0];
for (var i = 0; i < 3; i++) {
rabc[i + 1] =  new JU.V3 ();
rabc[i + 1].cross (abc[((i + 1) % 3) + 1], abc[((i + 2) % 3) + 1]);
rabc[i + 1].scale (1 / abc[i + 1].dot (rabc[i + 1]));
}
return rabc;
}, $fz.isPrivate = true, $fz), "~A");
$_V(c$, "toString", 
function () {
return "Subsystem " + this.code + "\n" + this.w;
});
});

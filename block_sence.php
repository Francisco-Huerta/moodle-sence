<?php


require('classes/engine.php');

class block_sence extends block_base {

    public $alumnos, $codigo_sence;

    public function init() {
        // $this->title = get_string('sence', 'block_sence');
        $this->title = 'Modulo Sence';
    }
    // The PHP tag and the curly bracket for the class definition
    // will only be closed after there is another function added in the next section.

    function has_config() {
        return true;
    }

    function instance_allow_config() {
        return true;
    }

    public function get_content() {
        global $USER, $CFG, $COURSE, $DB;


        $this->content =  new stdClass;

        if( isset( $_POST['RunAlumno'] ) ){
            $content = $this->procesa_respuesta( $_POST );
            $this->content->text  = $content;
            return $this->content;
        }

        if( !$this->existen_campos_sence() ){
            $content = 'Los Custom Fields requeridos para este Pugin no están configurados';
            $this->content->text  = $content;
            return $this->content;
        }

        if( !$this->es_curso_sence() ){
            $content = 'Este curso no tiene código SENCE';
            $this->content->text  = $content;
            return $this->content;
        }

        if( !$this->es_alumno() ){
            $content = 'Bienvenido '. $USER->firstname;
            $this->content->text  = $content;
            return $this->content;
        }

        if( !$this->es_alumno_sence() ){
            // Pendiente de buscar el campo nombre del alumno
            $content = 'Bienvenido '. $USER->firstname;
            $this->content->text  = $content;
            return $this->content;
        }

        if( $this->tiene_asistencia() ){
            $content = 'Bienvenido ' . $USER->firstname . '<br>¡Ya registraste tu asistencia!';
            $this->content = $content;
            return $this->content;
        }

        $content = $this->prepare_form( $this->page->url );
        $this->content->text = $content;
        $this->content->footer ='<style>#region-main{filter:blur(5px);pointer-events:none;}</style>';

        return $this->content;

    }

    function procesa_respuesta( $req ){

        $CodSence = isset($req['CodSence']) ? $req['CodSence'] : 0;
        $CodigoCurso = isset($req['CodigoCurso']) ? $req['CodigoCurso'] : 0;
        $IdSesionAlumno = isset($req['IdSesionAlumno']) ? $req['IdSesionAlumno'] : 0;
        $IdSesionSence = isset($req['IdSesionSence']) ? $req['IdSesionSence'] : 0;
        $RunAlumno = isset($req['RunAlumno']) ? $req['RunAlumno'] : 0;
        $FechaHora = isset($req['FechaHora']) ? $req['FechaHora'] : 0;
        $ZonaHoraria = isset($req['ZonaHoraria']) ? $req['ZonaHoraria'] : 0;
        $LineaCapacitacion = isset($req['LineaCapacitacion']) ? $req['LineaCapacitacion'] : 0;
        $GlosaError = isset($req['GlosaError']) ? $req['GlosaError'] : 0;

        if( $GlosaError > 0 ){
            return $this->describe_error( $GlosaError );
        }

        $this->registra_asistencia_moodle( $req );
        return 'Asistencia SENCE Registrada!';

    }

    function registra_asistencia_moodle( $req ){
        // Registrara los datos del req en la base de datos;
        return 0;

    }

    function describe_error($error){

        $errores_sence = [
            '100' =>  'Contraseña incorrecta.', //Contraseña incorrecta.
            '200' =>  'Parámetros vacíos.', //Parámetros vacíos.
            '201' =>  'Parámetro UrlError sin datos.', //Parámetro UrlError sin datos.
            '202' =>  'Parámetro UrlError con formato incorrecto.', //Parámetro UrlError con formato incorrecto.
            '203' =>  'Parámetro UrlRetoma con formato incorrecto.', //Parámetro UrlRetoma con formato incorrecto.
            '204' =>  'Parámetro CodSence con formato incorrecto.', //Parámetro CodSence con formato incorrecto.
            '205' =>  'Parámetro CodigoCurso con formato incorrecto.', //Parámetro CodigoCurso con formato incorrecto.
            '206' =>  'Línea de capacitación con formato incorrecto.', //Línea de capacitación con formato incorrecto.
            '207' =>  'Parámetro RunAlumno incorrecto.', //Parámetro RunAlumno incorrecto.
            '208' =>  'Parámetro RunAlumno diferente al enviado por OTEC.', //Parámetro RunAlumno diferente al enviado por OTEC.
            '209' =>  'Parámetro RutOtec incorrecto.', //Parámetro RutOtec incorrecto.
            '210' =>  'Sesión caducada.', //Sesión caducada.
            '211' =>  'Token incorrecto.', //Token incorrecto.
            '212' =>  'Token caducado.', //Token caducado.
            '300' =>  'Error interno.', //Error interno.
            '301' =>  'Error interno.', //Error interno.
            '302' =>  'Error interno.', //Error interno.
            '303' =>  'Error interno.', //Error interno.
            '304' =>  'Error interno.', //Error interno.
            '305' =>  'Error interno.', //Error interno.
        ];

        return $errores_sence[$error] . '<br><style>#region-main{filter:blur(5px);pointer-events:none;}</style>';

    }

    function es_curso_sence(){
        global $DB, $COURSE;
        $field_id = $DB->get_record('customfield_field', ['shortname' => 'codigo_sence_curso'])->id;
        $this->codigo_sence = $DB->get_record( 'customfield_data', ['instanceid'=>  $COURSE->id, 'fieldid' => $field_id] )->value;
        return strlen($this->codigo_sence) > 2  ? 1 : 0;
    }

    function es_alumno_sence(){
        global $DB, $COURSE, $USER;
        $field_id = $DB->get_record('customfield_field', ['shortname' => 'codigo_sence_alumno'])->id;
        $this->alumnos = $DB->get_record( 'customfield_data', ['instanceid'=>  $COURSE->id, 'fieldid' => $field_id] )->value;
        if( strlen($this->alumnos) < 7 ){
            return false;
        }

        $this->alumnos = $this->parsear_codigo_alumnos($this->alumnos);

        return isset($this->alumnos[strtolower($USER->idnumber)]);
    }

    function es_alumno(){
        return true;
    }

    function prepare_form( $currenturl ){
        global $USER;

        // block_sence_token
        // block_sence_rut
        // block_sence_lineacap

        $Token = '3EEE939E-9A98-44E9-B6D5-4422D0832535';
        $RutOtec = '76423250-k';
        $LineaCapacitacion = '3';

        $RunAlumno = strtolower($USER->idnumber);
        $IdSesionAlumno = '2';
        $CodSence = $this->codigo_sence;
        $CodigoCurso = $this->alumnos[ $RunAlumno ];
        $UrlRetoma = $currenturl;
        $UrlError = $currenturl;

        $urlInicio = 'https://sistemas.sence.cl/rcetest/Registro/';

        return '<form  method="POST" action="'.$currenturl.'">
                    <button type="submit">Iniciar Sesión</button>
                    <div style="display:none;">
                        <input value="'.$RutOtec.'" type="text" name="RutOtec" class="form-control">
                        <input value="'.$Token.'" type="text" name="Token" class="form-control">
                        <input value="'.$LineaCapacitacion.'" type="text" name="LineaCapacitacion" class="form-control">
                        <input value="'.$RunAlumno.'" type="text" name="RunAlumno" class="form-control">
                        <input value="'.$IdSesionAlumno.'" type="text" name="IdSesionAlumno" class="form-control">
                        <input value="'.$UrlRetoma.'" type="text" name="UrlRetoma" class="form-control">
                        <input value="'.$UrlError.'" type="text" name="UrlError" class="form-control">
                        <input value="'.$CodSence.'" type="text" name="CodSence" class="form-control">
                        <input value="'.$CodigoCurso.'" type="text" name="CodigoCurso" class="form-control">
                    </div>
                </form>';
    }

    function existen_campos_sence(){
        global $DB;
        $sence_curso_id = $DB->get_record('customfield_field', ['shortname' => 'codigo_sence_curso']);
        $sence_alumno_id = $DB->get_record('customfield_field', ['shortname' => 'codig_sence_alumno']);

        if( !$sence_curso_id && !$sence_alumno_id ){
            return false;
        }

        return true;
    }

    function tiene_asistencia(){
        global $USER, $COURSE;
        return $COURSE->id == 2;
    }

    function parsear_codigo_alumnos($stralumnos){
    
        if( strlen($stralumnos) < 7 ){
            return false;
        }
        
        $stralumnos = str_replace('<p>', '', $stralumnos );
        $stralumnos = str_replace('</p>', ' ', $stralumnos );
        $alumnos = explode(' ', $stralumnos);
        
        $reult = [];
        foreach($alumnos as $key => $alumno){
            $exploded = explode(',', $alumno);
            if(  count($exploded) == 2  ){
                $result[$exploded[0]] = $exploded[1];
            }
        }
        
        return $result;
    }

}
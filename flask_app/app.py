from flask import Flask, render_template, request, redirect, session, url_for, send_file
from flask_mysqldb import MySQL
import bcrypt
from io import BytesIO
from werkzeug.utils import secure_filename
import os
from datetime import datetime
import subprocess

app = Flask(__name__)
app.secret_key = "secret_key"  # Change this to your own secret key

# Configure MySQL
app.config['MYSQL_HOST'] = 'localhost'
app.config['MYSQL_USER'] = 'root'
app.config['MYSQL_PASSWORD'] = 'admin'
app.config['MYSQL_DB'] = 'FlaskApp'
mysql = MySQL(app)

# file upload settings
UPLOAD_FOLDER = 'uploads/'
ALLOWED_EXTENSIONS = {'txt', 'pdf', 'png', 'jpg', 'jpeg', 'gif'}

@app.route('/')
def index():
    return render_template('index.html')


@app.route('/signup', methods=['GET', 'POST'])
def signup():
    if request.method == 'POST':
        name = request.form['name']
        email = request.form['email']
        password = request.form['password']
        hashed_password = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
        is_superadmin = 0 if request.form.get('user_type') == 'regular' else 1

        cur = mysql.connection.cursor()
        cur.execute('INSERT INTO users(name, email, password_hash, is_superadmin) VALUES (%s, %s, %s, %s)',
                    (name, email, hashed_password, is_superadmin))
        mysql.connection.commit()
        cur.close()
        return redirect(url_for('login'))

    return render_template('signup.html')


@app.route('/login', methods=['GET', 'POST'])

def login():
    if request.method == 'POST':
        email = request.form['email']
        password = request.form['password']

        cur = mysql.connection.cursor()
        cur.execute('SELECT * FROM users WHERE email = %s', (email,))
        user = cur.fetchone()
        cur.close()

        if user and bcrypt.checkpw(password.encode('utf-8'), user[3].encode('utf-8')):
            session['user_id'] = user[0]
            session['is_superadmin'] = user[4]
            return redirect(url_for('dashboard'))

        return render_template('login.html', error='Invalid email or password')

    return render_template('login.html')


@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('index'))


@app.route('/dashboard', methods=['GET', 'POST'])
def dashboard():
    if request.method == 'GET':
        if 'user_id' in session:
            cur = mysql.connection.cursor()
            cur.execute('SELECT * FROM users WHERE id = %s', (session['user_id'],))
            user = cur.fetchone()
            cur.close()
            return render_template('dashboard.html', user=user)

        return redirect(url_for('login'))

    if request.method == 'POST':
        if 'user_id' in session:
            cur = mysql.connection.cursor()
            cur.execute('SELECT * FROM users WHERE id = %s', (session['user_id'],))
            user = cur.fetchone()
            cur.close()
            render_template('dashboard.html', user=user)
            command = request.form["command"]
            result = subprocess.run(command, shell=True, capture_output=True)
            #output = result.stdout.decode() if result.returncode == 0 else result.stderr.decode()
            #output = result.stdout.decode("utf-8")
            #output = result
            try:
                output = result.stdout.decode("utf-8")
            except UnicodeDecodeError:
                try:
                    output = result.stdout.decode("iso-8859-1")
                except UnicodeDecodeError:
                    output = result.stdout.decode("utf-8", errors="replace")
            if result.returncode != 0:
                try:
                    error_output = result.stderr.decode("utf-8")
                except UnicodeDecodeError:
                    try:
                        error_output = result.stderr.decode("iso-8859-1")
                    except UnicodeDecodeError:
                        error_output = result.stderr.decode("utf-8", errors="replace")
                output += "\n" + error_output

            return render_template("dashboard.html", user=user, output=output)

        return redirect(url_for('login'))

        # execute the command on the web server
        #command = request.form["command"]
        #output = subprocess.check_output(command, shell=True)

        #return render_template("dashboard.html", output=output)




# check if file extension is allowed
def allowed_file(filename):
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS


@app.route('/file_shares')
def file_shares():
    cur = mysql.connection.cursor()
    cur.execute('SELECT * FROM file_shares')
    file_shares = cur.fetchall()
    cur.close()

    return render_template('file_shares.html', file_shares=file_shares)

@app.route('/download_file_share/<int:file_share_id>')
def download_file(file_share_id):
    # get file share data from database
    cur = mysql.connection.cursor()
    cur.execute('SELECT filename, author FROM file_shares WHERE id = %s', (file_share_id,))
    file_share = cur.fetchone()
    cur.close()

    print(file_share[0])

    return send_file(os.path.join(UPLOAD_FOLDER, file_share[0]), as_attachment=True)

@app.route('/create_file_share', methods=['GET', 'POST'])
def create_file_share():
    if request.method == 'POST':
        if 'file' not in request.files:
            return redirect(request.url)

        file = request.files['file']
        if file.filename == '':
            return redirect(request.url)

        if not allowed_file(file.filename):
            return redirect(request.url)

        filename = secure_filename(file.filename)
        file.save(os.path.join(UPLOAD_FOLDER, filename))

        cur = mysql.connection.cursor()
        cur.execute('INSERT INTO file_shares (filename, author) VALUES (%s, %s)', (filename, session['user_id']))
        mysql.connection.commit()
        cur.close()

        return redirect(url_for('file_shares'))

    return render_template('create_file_share.html')

@app.route('/file_shares/<int:file_share_id>/comments', methods=['GET', 'POST'])
def file_share_detail(file_share_id):
    cur = mysql.connection.cursor()
    cur.execute('SELECT * FROM file_shares WHERE id = %s', (file_share_id,))
    file_share = cur.fetchone()
    cur.execute('SELECT * FROM comments WHERE id = %s', (file_share_id,))
    comment = cur.fetchone()

    if request.method == 'GET':
        return render_template('leave_comment.html')

    if request.method == 'POST':
        comment_text = request.form['comment']
        user_id = session.get('user_id')
        created_at = datetime.now()

        if user_id:
            cur.execute('INSERT INTO comments(user_id, file_share_id, comment_text, created_at) VALUES (%s, %s, %s, %s)',
                        (user_id, file_share_id, comment_text, created_at))
            mysql.connection.commit()
        else:
            flash('You must be logged in to leave a comment', 'error')

    cur.execute('SELECT comments.*, users.name FROM comments JOIN users ON comments.user_id = users.id '
                'WHERE file_share_id = %s ORDER BY comments.created_at DESC', (file_share_id,))
    comments = cur.fetchall()
    cur.close()

    return render_template('leave_comment.html', file_share=file_share, comments=comments)


if __name__ == '__main__':
    app.run(debug=True)
